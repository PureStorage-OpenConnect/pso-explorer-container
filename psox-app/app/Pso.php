<?php

/**
 * Pso module
 * This module retrieves information from Kubernetes and Pure Storage products
 * and stores the result in Redis using models. It is also be used to retrieve
 * the information from Redis.
 *
 * @category Class
 * @package  PSO_Explorer
 * @author   Remko Deenik <rdeenik@purestorage.com>
 * @license  https://raw.githubusercontent.com/PureStorage-OpenConnect/pso-explorer/master/LICENSE Apache 2
 * @link     https://github.com/PureStorage-OpenConnect/pso-explorer-container
 *
 * php version 7.4
 */

namespace App;

use App\Api\FlashArrayApi;
use App\Api\FlashBladeApi;
use App\Api\k8s\PodLog;
use App\Api\k8s\VolumeSnapshotClass;
use App\Api\k8s\VolumeSnapshot;
use App\Http\Classes\PsoArray;
use App\Http\Classes\PsoBackendVolume;
use App\Http\Classes\PsoDeployment;
use App\Http\Classes\PsoInformation;
use App\Http\Classes\PsoJob;
use App\Http\Classes\PsoLabels;
use App\Http\Classes\PsoNamespace;
use App\Http\Classes\PsoNode;
use App\Http\Classes\PsoPersistentVolume;
use App\Http\Classes\PsoPersistentVolumeClaim;
use App\Http\Classes\PsoPod;
use App\Http\Classes\PsoVolumeSnapshot;
use App\Http\Classes\PsoVolumeSnapshotClass;
use App\Http\Classes\PsoStatefulSet;
use App\Http\Classes\PsoStorageClass;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Kubernetes\API\APIService;
use Kubernetes\API\ConfigMap;
use Kubernetes\API\Deployment;
use Kubernetes\API\Job;
use Kubernetes\API\Node;
use Kubernetes\API\PersistentVolume;
use Kubernetes\API\PersistentVolumeClaim;
use Kubernetes\API\Pod;
use Kubernetes\API\Secret;
use Kubernetes\API\StatefulSet;
use Kubernetes\API\StorageClass;
use KubernetesRuntime\Client;

class Pso
{
    public const VALID_PSO_DATA_KEY = 'pso:timestamp';
    public const PSO_UPDATE_KEY = 'pso:do_update';
    public const PURE_PROVISIONERS = ['pure-provisioner', 'pure-csi'];

    private $master = null;
    private $authentication = null;
    private $refreshTimeout = 300;

    public $psoInfo = null;
    public $psoFound = false;
    public $errorSource = '';
    public $errorMessage = '';

    public function __construct()
    {
        $this->psoInfo = new PsoInformation();

        // Check if running in a container:
        // If in container, use in-cluster Kubernetes credentials to connect
        // with the Kubernetes API. For development, use local hosts file for
        // cluster IP (kubernetes.default.svc) and locally stored credentials
        if (file_exists('/var/run/secrets/kubernetes.io')) {
            // Use for in cluster credentials
            if (file_exists('/run/secrets/rhsm')) {
                $this->master = 'https://kubernetes.default.svc.cluster.local';
            } else {
                $this->master = 'https://kubernetes.default.svc';
            }
            $this->authentication = [
                'caCert' => '/var/run/secrets/kubernetes.io/serviceaccount/ca.crt',
                'token' => '/var/run/secrets/kubernetes.io/serviceaccount/token',
            ];
        } else {
            // Use for development workstation
            $this->master = 'https://kubernetes.default.svc';
            $this->authentication = [
                'caCert' => '/etc/pso-explorer/ca.crt',
                'token' => '/etc/pso-explorer/token'
            ];
        }

        // Set the expiration time for the collected data
        $this->refreshTimeout = getenv('PSOX_CACHE_TIMEOUT') ?: env('PSOX_CACHE_TIMEOUT', '300');

        // Initialize the psoInfo variable
        $this->refreshData();
    }

    /**
     * Convert float in bytes to formatted string
     *
     * @return string
     */
    private function formatBytes($bytes, $precision = 2, $format = 1)
    {
        if ($format == 1) {
            // Format == 1: Storage capacity
            $units = array('Bi', 'Ki', 'Mi', 'Gi', 'Ti', 'Pi', 'Ei');
        } elseif ($format == 2) {
            // Format == 1: Bandwidth
            $units = array('b', 'Kb', 'Mb', 'Gb', 'Tb', 'Pb', 'Eb');
        } else {
            // Format == 3: Regular number
            $units = array('', 'K', 'M', 'G', 'T', 'P', 'E');
        }

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        if (array_key_exists(intval($pow), $units)) {
            return round($bytes, $precision) . ' ' . $units[$pow];
        } else {
            return round($bytes, $precision);
        }
    }

    /**
     * Check if string ($haystack) starts with substring ($needle)
     *
     * @return boolean
     */
    private function startsWith($needle, $haystack)
    {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    private function objectToArray($object)
    {
        $array = [];
        if ($object == null) {
            return $array;
        }

        foreach (($object ?? []) as $key => $value) {
            if (is_object($value)) {
                $array[$key] = $this->objectToArray($value);
            } elseif (is_array($value)) {
                array_push($array, [$key => $this->objectToArray($value)]);
            } else {
                $array[$key] = $value;
            }
        }
        return $array;
    }

    private function addFlashArrayVolInfo($array, $query)
    {
        $result = false;
        $listPv_names = PsoPersistentVolume::items(PsoPersistentVolume::PREFIX, 'name');

        $totalUsed = $this->psoInfo->totalUsed;
        $totalSize = $this->psoInfo->totalSize;
        $totalOrphanedUsed = $this->psoInfo->totalOrphanedUsed;
        $totalSnapshotUsed = $this->psoInfo->totalSnapshotUsed;

        $fa = new FlashArrayApi();
        $fa->authenticate($array->mgmtEndPoint, $array->apiToken);

        $vols = $fa->getVolumes(
            [
                'names' => $query,
                'space' => 'true',
            ]
        );

        foreach (($vols ?? []) as $vol) {
            if ($this->startsWith($this->psoInfo->prefix . '-pvc-', $vol['name']) or ($vol['name'] == $query)) {
                if ($vol['name'] == $query) {
                    $name = PsoPersistentVolume::getNameBycsiVolumeHandle($query);
                } else {
                    $name = str_ireplace($this->psoInfo->prefix . '-', '', $vol['name']);
                }
                $myPv = new PsoPersistentVolume($name);

                $myPv->pureName = $vol['name'] ?? '';
                $myPv->pureSize = $vol['size'] ?? 0;
                $myPv->pureSizeFormatted = $this->formatBytes($myPv->pureSize, 2);
                $myPv->pureTotal = $vol['total'] ?? 0;
                $myPv->pureUsed = $vol['size'] * (1 - $vol['thin_provisioning'] ?? 0);
                $myPv->pureUsedFormatted = $this->formatBytes($myPv->pureUsed, 2);
                $myPv->pureDrr = $vol['data_reduction'] ?? 1;
                $myPv->pureThinProvisioning = $vol['thin_provisioning'] ?? 0;
                $myPv->pureArrayName = $array->name;
                $myPv->pureArrayType = 'FA';
                $myPv->pureArrayMgmtEndPoint = $array->mgmtEndPoint;
                $myPv->pureSnapshots = $vol['snapshots'] ?? 0;
                $myPv->pureVolumes = $vol['volumes'] ?? 0;
                $myPv->pureSharedSpace = $vol['shared_space'] ?? 0;
                $myPv->pureTotalReduction = $vol['total_reduction'] ?? 1;
                $totalUsed = $totalUsed + ($vol['size'] ?? 0)  *
                    (1 - ($vol['thin_provisioning'] ?? 0));
                $totalSize = $totalSize + ($vol['size'] ?? 0);
                $totalSnapshotUsed = $totalSnapshotUsed + ($vol['snapshots'] ?? 0);

                // If the FA volume has no PV it's orphaned
                if (!in_array($name, $listPv_names)) {
                    $myPv->isOrphaned = $name;
                    $totalOrphanedUsed = $totalOrphanedUsed + ($vol['size'] ?? 0) *
                        (1 - ($vol['thin_provisioning'] ?? 0));
                }

                $result = true;
            }

            if ($this->startsWith($this->psoInfo->prefix . '-pso-db_', $vol['name'])) {
                $pureArrayNameVolName = $array->name . ':' . $vol['name'];
                $backendVol = new PsoBackendVolume($pureArrayNameVolName);

                $backendVol->pureName = $vol['name'];
                $backendVol->pureSize = $vol['size'] ?? 0;
                $backendVol->pureSizeFormatted = $this->formatBytes($backendVol->pureSize, 2);
                $backendVol->pureUsed = $vol['size'] * (1 - $vol['thin_provisioning'] ?? 0);
                $backendVol->pureUsedFormatted = $this->formatBytes($backendVol->pureUsed, 2);
                $backendVol->pureDrr = $vol['data_reduction'] ?? 1;
                $backendVol->pureThinProvisioning = $vol['thin_provisioning'] ?? 0;
                $backendVol->pureArrayName = $array->name;
                $backendVol->pureArrayType = 'FA';
                $backendVol->pureArrayMgmtEndPoint = $array->mgmtEndPoint;
                $backendVol->pureSharedSpace = $vol['shared_space'] ?? 0;
                $backendVol->pureTotalReduction = $vol['total_reduction'] ?? 1;

                if (substr($pureArrayNameVolName, -2) == '-u') {
                    $backendVol->unhealthy = true;
                    $backendVol2 = new PsoBackendVolume(substr($pureArrayNameVolName, 0, -2));
                    $backendVol2->unhealthy = true;
                }
            }
        }
        $this->psoInfo->totalUsed = $totalUsed;
        $this->psoInfo->totalSize = $totalSize;
        $this->psoInfo->totalOrphanedUsed = $totalOrphanedUsed;
        $this->psoInfo->totalSnapshotUsed = $totalSnapshotUsed;

        return $result;
    }

    private function addFlashArrayPerfInfo($array, $query)
    {
        $listPv_names = PsoPersistentVolume::items(PsoPersistentVolume::PREFIX, 'name');

        $totalIopsRead = $this->psoInfo->totalIopsRead;
        $totalIopsWrite = $this->psoInfo->totalIopsWrite;
        $totalBwRead = $this->psoInfo->totalBwRead;
        $totalBwWrite = $this->psoInfo->totalBwWrite;
        $lowMsecRead = -1;
        $lowMsecWrite = -1;
        $highMsecRead = 0;
        $highMsecWrite = 0;

        $fa = new FlashArrayApi();
        $fa->authenticate($array->mgmtEndPoint, $array->apiToken);

        $volsPerf = $fa->getVolumes(
            [
                'names' => $query,
                'action' => 'monitor',
            ]
        );

        foreach (($volsPerf ?? []) as $volPerf) {
            if ($this->startsWith($this->psoInfo->prefix . '-pvc-', $volPerf['name']) or ($volPerf['name'] == $query)) {
                if ($volPerf['name'] == $query) {
                    $name = PsoPersistentVolume::getNameBycsiVolumeHandle($query);
                } else {
                    $name = str_ireplace($this->psoInfo->prefix . '-', '', $volPerf['name']);
                }

                $myPv = new PsoPersistentVolume($name);
                $myPv->pureReadsPerSec = $volPerf['reads_per_sec'] ?? 0;
                $myPv->pureWritesPerSec = $volPerf['writes_per_sec'] ?? 0;
                $myPv->pureInputPerSec = $volPerf['input_per_sec'] ?? 0;
                $myPv->pureInputPerSecFormatted = $this->formatBytes(
                    $volPerf['input_per_sec'],
                    1,
                    2
                ) . '/s';
                $myPv->pureOutputPerSec = $volPerf['output_per_sec'] ?? 0;
                $myPv->pureOutputPerSecFormatted = $this->formatBytes(
                    $volPerf['output_per_sec'],
                    1,
                    2
                ) . '/s';
                $myPv->pureUsecPerReadOp = round(
                    $volPerf['usec_per_read_op'] / 1000,
                    2
                );
                $myPv->pureUsecPerWriteOp = round(
                    $volPerf['usec_per_write_op'] / 1000,
                    2
                );

                // Only count performance for non-orphaned volumes
                if (in_array($name, $listPv_names)) {
                    $totalIopsRead = $totalIopsRead + $volPerf['reads_per_sec'] ?? 0;
                    $totalIopsWrite = $totalIopsWrite + $volPerf['writes_per_sec'] ?? 0;
                    $totalBwRead = $totalBwRead + $volPerf['output_per_sec'] ?? 0;
                    $totalBwWrite = $totalBwWrite + $volPerf['input_per_sec'] ?? 0;

                    if (($volPerf['usec_per_read_op'] / 1000 < $lowMsecRead) or ($lowMsecRead = -1)) {
                        $lowMsecRead = $volPerf['usec_per_read_op'] / 1000;
                    }
                    if (($volPerf['usec_per_write_op'] / 1000 < $lowMsecWrite) or ($lowMsecWrite = -1)) {
                        $lowMsecWrite = $volPerf['usec_per_write_op'] / 1000;
                    }
                    if ($volPerf['usec_per_read_op'] / 1000 > $highMsecRead) {
                        $highMsecRead = $volPerf['usec_per_read_op'] / 1000;
                    }
                    if ($volPerf['usec_per_write_op'] / 1000 > $highMsecWrite) {
                        $highMsecWrite = $volPerf['usec_per_write_op'] / 1000;
                    }
                }
            }
        }
        $this->psoInfo->totalIopsRead = $totalIopsRead;
        $this->psoInfo->totalIopsWrite = $totalIopsWrite;
        $this->psoInfo->totalBwRead = $totalBwRead;
        $this->psoInfo->totalBwWrite = $totalBwWrite;
    }

    private function addFlashArrayHistInfo($array, $query)
    {
        $fa = new FlashArrayApi();
        $fa->authenticate($array->mgmtEndPoint, $array->apiToken);

        try {
            $volsHist = $fa->getVolumes(
                [
                    'names' => $query,
                    'space' => 'true',
                    'historical' => '24h',
                ]
            );

            $volHistSize = [];
            foreach (($volsHist ?? []) as $volHist) {
                if ($this->startsWith($this->psoInfo->prefix . '-pvc-', $volHist['name']) or ($volHist['name'] == $query)) {
                    if ($volHist['name'] == $query) {
                        $name = PsoPersistentVolume::getNameBycsiVolumeHandle($query);
                    } else {
                        $name = str_ireplace($this->psoInfo->prefix . '-', '', $volHist['name']);
                    }

                    if (
                        isset($volHistSize[$name]['firstDate'])
                        and (strtotime($volHist['time']) < $volHistSize[$name]['firstDate'])
                    ) {
                        $volHistSize[$name]['firstTotal'] = $volHist['total'];
                        $volHistSize[$name]['firstDate'] = strtotime($volHist['time']);
                    } elseif (!isset($volHistSize[$name]['firstDate'])) {
                        $volHistSize[$name]['firstTotal'] = $volHist['total'];
                        $volHistSize[$name]['firstDate'] = strtotime($volHist['time']);
                    }
                }
            }

            foreach (PsoPersistentVolume::items(PsoPersistentVolume::PREFIX, 'name') as $name) {
                $myPv = new PsoPersistentVolume($name);

                if (isset($volHistSize[$name]['firstTotal']) and ($myPv->pureArrayType == 'FA')) {
                    $myPv->pure24hHistoricTotal = $volHistSize[$name]['firstTotal'];
                }
            }
        } catch (Exception $e) {
            // Log error message
            Log::warning('xxx Error retrieving historical space usage for volumes.');
            Log::debug('      - Message: "' . $e->getMessage() . '"');
            Log::debug('      - File: "' . $e->getFile() . '"');
            Log::debug('      - Line: "' . $e->getLine() . '"');
            unset($e);
        }
    }

    private function addFlashArraySnapInfo($array, $query)
    {
        $fa = new FlashArrayApi();
        $fa->authenticate($array->mgmtEndPoint, $array->apiToken);

        $snaps = $fa->getVolumes(
            [
                'names' => $query,
                'space' => 'true',
                'snap' => 'true',
            ]
        );

        foreach (($snaps ?? []) as $snap) {
            if (
                $this->startsWith($this->psoInfo->prefix . '-pvc-', $snap['name'])
                or $this->startsWith($query, $snap['name'])
            ) {
                $snapPrefix = '.snapshot-';
                $pureVolName = substr($snap['name'], 0, strpos($snap['name'], $snapPrefix));
                $uid = substr($snap['name'], strpos($snap['name'], $snapPrefix) + strlen($snapPrefix));

                $mysnap = new PsoVolumeSnapshot($uid);
                if (($mysnap->name == '') and ($mysnap->namespace == '') and ($mysnap->sourceName == '')) {
                    $pureVolName = substr($snap['name'], 0, strpos($snap['name'], '.'));

                    $mysnap->name = $pureVolName;
                    $mysnap->namespace = 'Unknown';
                    $mysnap->sourceName = $pureVolName;
                    $mysnap->readyToUse = 'Ready';
                    $mysnap->errorMessage = 'This snaphot is (no longer) managed by Kubernetes';
                    $mysnap->orphaned = $uid;
                }

                $mysnap->pureName = $snap['name'];
                $mysnap->pureVolName = $pureVolName;
                $mysnap->pureSize = $snap['size'] ?? 0;
                $mysnap->pureSizeFormatted = $this->formatBytes($mysnap->pureSize, 2);
                ;
                $mysnap->pureUsed = $snap['total'] ?? 0;
                $mysnap->pureUsedFormatted = $this->formatBytes($mysnap->pureUsed, 2);
                ;

                $mysnap->pureArrayName = $array->name;
                $mysnap->pureArrayType = 'FA';
                $mysnap->pureArrayMgmtEndPoint = $array->mgmtEndPoint;
            }
        }
    }

    private function addFlashBladeVolInfo($array, $query)
    {
        $result = false;
        $listPv_names = PsoPersistentVolume::items(PsoPersistentVolume::PREFIX, 'name');

        $totalUsed = $this->psoInfo->totalUsed;
        $totalSize = $this->psoInfo->totalSize;
        $totalOrphanedUsed = $this->psoInfo->totalOrphanedUsed;
        $totalSnapshotUsed = $this->psoInfo->totalSnapshotUsed;
        $totalIopsRead = $this->psoInfo->totalIopsRead;
        $totalIopsWrite = $this->psoInfo->totalIopsWrite;
        $totalBwRead = $this->psoInfo->totalBwRead;
        $totalBwWrite = $this->psoInfo->totalBwWrite;

        $fb = new FlashBladeApi($array->mgmtEndPoint, $array->apiToken);

        try {
            $fb->authenticate();

            $filesystems = $fb->getFileSystems(
                [
                    'names' => $query,
                    'space' => 'true',
                    'destroyed' => false,
                ]
            );
        } catch (Exception $e) {
            // Log error message
            Log::error('  xxx Error getting FileSystems for "' . $array->mgmtEndPoint . '"');
            Log::debug('      - Message: "' . $e->getMessage() . '"');
            Log::debug('      - File: "' . $e->getFile() . '"');
            Log::debug('      - Line: "' . $e->getLine() . '"');

            unset($e);
            $filesystems = null;
        }

        foreach (($filesystems['items'] ?? []) as $filesystem) {
            if (
                $this->startsWith($this->psoInfo->prefix . '-pvc-', $filesystem['name']) or
                ($filesystem['name'] == $query)
            ) {
                if ($filesystem['name'] == $query) {
                    $name = PsoPersistentVolume::getNameBycsiVolumeHandle($query);
                } else {
                    $name = str_ireplace($this->psoInfo->prefix . '-', '', $filesystem['name']);
                }

                $myPv = new PsoPersistentVolume($name);
                $myPv->pureName = $filesystem['name'] ?? '';
                $myPv->pureSize = $filesystem['provisioned'] ?? 0;
                $myPv->pureSizeFormatted = $this->formatBytes($myPv->pureSize, 2);
                $myPv->pureUsed = $filesystem['space']['virtual'] ?? 0;
                $myPv->pureUsedFormatted = $this->formatBytes($myPv->pureUsed, 2);
                $myPv->pureDrr = $filesystem['space']['data_reduction'] ?? 1;
                $myPv->pureThinProvisioning = 0;
                $myPv->pureArrayName = $array->name;
                $myPv->pureArrayType = 'FB';
                $myPv->pureArrayMgmtEndPoint = $array->mgmtEndPoint;
                $myPv->pureSnapshots = $filesystem['space']['snapshots'] ?? 0;
                $myPv->pureVolumes = $filesystem['space']['unique'] ?? 0;
                $myPv->pureSharedSpace = 0;
                $myPv->pureTotalReduction = $filesystem['space']['data_reduction'] ?? 1;

                if (in_array($name, $listPv_names)) {
                    $totalUsed = $totalUsed + ($filesystem['space']['virtual'] ?? 0);
                    $totalSize = $totalSize + ($filesystem['provisioned'] ?? 0);
                } else {
                    $myPv->isOrphaned = $name;
                    $totalOrphanedUsed = $totalOrphanedUsed + ($filesystem['space']['virtual'] ?? 0);
                }

                $totalSnapshotUsed = $totalSnapshotUsed + ($filesystem['space']['snapshots'] ?? 0);

                $fsPerf = $fb->getFileSystemsPerformance(
                    [
                        'names' => $filesystem['name'],
                        'protocol' => 'nfs',
                    ]
                );

                foreach (($fsPerf['items'] ?? []) as $fsPerf) {
                    $myPv->pureReadsPerSec = $fsPerf['reads_per_sec'] ?? 0;
                    $myPv->pureWritesPerSec = $fsPerf['writes_per_sec'] ?? 0;
                    $myPv->pureInputPerSec = $fsPerf['write_bytes_per_sec'] ?? 0;
                    $myPv->pureInputPerSecFormatted = $this->formatBytes(
                        $myPv->pureInputPerSec,
                        1,
                        2
                    ) . '/s';
                    $myPv->pureOutputPerSec = $fsPerf['read_bytes_per_sec'];
                    $myPv->pureOutputPerSecFormatted = $this->formatBytes(
                        $myPv->pureOutputPerSec,
                        1,
                        2
                    ) . '/s';
                    $myPv->pureUsecPerReadOp = round(
                        $myPv->pureUsecPerReadOp / 1000,
                        2
                    );
                    $myPv->pureUsecPerWriteOp = round(
                        $myPv->pureUsecPerWriteOp / 1000,
                        2
                    );

                    $totalIopsRead = $totalIopsRead + $myPv->pureReadsPerSec;
                    $totalIopsWrite = $totalIopsWrite + $myPv->pureWritesPerSec;
                    $totalBwRead = $totalBwRead + $myPv->pureOutputPerSec;
                    $totalBwWrite = $totalBwWrite + $myPv->pureInputPerSec;
                }
                $result = true;
            }

            if ($this->startsWith($this->psoInfo->prefix . '-pso-db_', $filesystem['name'])) {
                $pureArrayNameVolName = $array->name . ':' . $filesystem['name'];
                $backendVol = new PsoBackendVolume($pureArrayNameVolName);

                $backendVol->pureName = $filesystem['name'];
                $backendVol->pureSize = $filesystem['provisioned'] ?? 0;
                $backendVol->pureSizeFormatted = $this->formatBytes($backendVol->pureSize, 2);
                $backendVol->pureUsed = $filesystem['space']['virtual'];
                $backendVol->pureUsedFormatted = $this->formatBytes($backendVol->pureUsed, 2);
                $backendVol->pureDrr = $filesystem['space']['data_reduction'] ?? 1;
                $backendVol->pureThinProvisioning = 0;
                $backendVol->pureArrayName = $array->name;
                $backendVol->pureArrayType = 'FB';
                $backendVol->pureArrayMgmtEndPoint = $array->mgmtEndPoint;
                $backendVol->pureSharedSpace = 0;
                $backendVol->pureTotalReduction = $filesystem['space']['data_reduction'] ?? 1;

                if (substr($pureArrayNameVolName, -2) == '-u') {
                    $backendVol->unhealthy = true;
                    $backendVol2 = new PsoBackendVolume(substr($pureArrayNameVolName, 0, -2));
                    $backendVol2->unhealthy = true;
                }
            }
        }

        $this->psoInfo->totalUsed = $totalUsed;
        $this->psoInfo->totalSize = $totalSize;
        $this->psoInfo->totalOrphanedUsed = $totalOrphanedUsed;
        $this->psoInfo->totalSnapshotUsed = $totalSnapshotUsed;
        $this->psoInfo->totalIopsRead = $totalIopsRead;
        $this->psoInfo->totalIopsWrite = $totalIopsWrite;
        $this->psoInfo->totalBwRead = $totalBwRead;
        $this->psoInfo->totalBwWrite = $totalBwWrite;

        return $result;
    }

    /**
     * Connect to Kubernetes to retrieve the Nodes
     *
     * @return boolean
     */
    private function getNodes()
    {
        // Log function call
        Log::info('       Call getNodes()');

        try {
            // Retrieve all Kubernetes StatefulSets for this cluster
            Client::configure($this->master, $this->authentication);
            $node = new Node();

            $nodeList = $node->list();
        } catch (Exception $e) {
            // Log error message
            Log::error('  xxx Error connecting to Kubernetes API at "' . $this->master . '"');
            Log::debug('      - Message: "' . $e->getMessage() . '"');
            Log::debug('      - File: "' . $e->getFile() . '"');
            Log::debug('      - Line: "' . $e->getLine() . '"');

            $this->psoFound = false;
            $this->errorSource = 'k8s';
            $this->errorMessage = $e->getMessage();
            unset($e);
            return false;
        }

        if (isset($nodeList->code)) {
            $this->psoFound = false;
            $this->errorSource = 'k8s';
            $this->errorMessage = 'Unable to list Nodes. Check the ClusterRoles and ClusterRoleBindings.';
            return false;
        }

        foreach (($nodeList->items ?? []) as $item) {
            $mynode = new PsoNode($item->metadata->uid);
            $mynode->name = $item->metadata->name ?? $item->metadata->uid;
            $mynode->pingErrors = false;

            $labels = [];
            foreach (($item->metadata->labels ?? []) as $key => $value) {
                array_push($labels, $key . '=' . $value);
            }
            $mynode->labels = $labels;
            $mynode->creationTimestamp = $item->metadata->creationTimestamp ?? '';
            $mynode->podCIDR = $item->spec->podCIDR ?? '';
            $mynode->podCIDRs = $item->spec->podCIDRs ?? [];
            $taints = [];
            foreach (($item->spec->taints ?? []) as $taint) {
                array_push($taints, $taint->key . '=' . $taint->value . ':' . $taint->effect);
            }
            $mynode->taints = $taints;
            $mynode->unschedulable = $item->spec->unschedulable ?? '';
            $mynode->architecture = $item->status->nodeInfo->architecture ?? '';
            $mynode->containerRuntimeVersion = $item->status->nodeInfo->containerRuntimeVersion ?? '';
            $mynode->kernelVersion = $item->status->nodeInfo->kernelVersion ?? '';
            $mynode->kubeletVersion = $item->status->nodeInfo->kubeletVersion ?? '';
            $mynode->osImage = $item->status->nodeInfo->osImage ?? '';
            $mynode->operatingSystem = $item->status->nodeInfo->operatingSystem ?? '';
            foreach (($item->status->addresses ?? []) as $address) {
                switch (strtolower($address->type)) {
                    case 'hostname':
                        $mynode->hostname = $address->address ?? '';
                        break;
                    case 'internalip':
                        $mynode->internalIP = $address->address ?? '';
                        break;
                }
            }

            $conditions = [];
            $conditionMessages = [];
            foreach (($item->status->conditions ?? []) as $condition) {
                $type = $condition->type ?? 'Status';
                $message = $condition->message ?? 'No message available';

                if ($condition->status == 'True') {
                    if (!in_array($type, $conditions)) {
                        array_push($conditions, $type);
                        array_push($conditionMessages, $type . ': ' . $message);
                    }
                } elseif ($condition->status == 'Unknown') {
                    if ($type == 'Ready') {
                        if (!in_array('Not Ready', $conditions)) {
                            array_push($conditions, 'Not Ready');
                            array_push($conditionMessages, $type . ': ' . $message);
                        }
                    } else {
                        if (!in_array($type, $conditions)) {
                            array_push($conditions, $type);
                            array_push($conditionMessages, $type . ': ' . $message);
                        }
                    }
                }
            }
            $mynode->conditions = $conditions;
            $mynode->conditionMessages = $conditionMessages;
        }
        return true;
    }

    /**
     * Connect to Kubernetes to find PSO namespace and prefix
     * and collect information about K8S PODs
     *
     * @return boolean
     */
    private function getPsoDetails()
    {
        // Log function call
        Log::info('       Call getPsoDetails()');

        // Initialize variables
        $this->psoFound = false;

        // Try to connect to Kubernetes cluster API, catch any curl errors
        // Using a custom timeout for the CURL request, so we don't timeout our session
        try {
            Client::configure($this->master, $this->authentication, ['timeout' => 10]);
            $pod = new Pod();
            $podList = $pod->list('');
        } catch (Exception $e) {
            // Log error message
            Log::error('  xxx Error connecting to Kubernetes API at "' . $this->master . '"');
            Log::debug('      - Message: "' . $e->getMessage() . '"');
            Log::debug('      - File: "' . $e->getFile() . '"');
            Log::debug('      - Line: "' . $e->getLine() . '"');

            // If we catch a CURL error, return an error message
            $this->errorSource = 'k8s';
            $this->errorMessage = $e->getMessage();
            unset($e);
            return false;
        }

        // If the CURL connection was successful, check if the response was also successful
        // This could be an authentication error for example
        if (isset($podList->status)) {
            if ($podList->status == 'Failure') {
                // Log error message
                Log::error('  xxx Error connecting to Kubernetes API at "' . $this->master . '"');

                // If status is set to Failure, we hit an error, so we return an error message
                $this->errorSource = 'k8s';
                $this->errorMessage = $podList->message ?? 'Unknown error occurred';
                return false;
            }
        }

        // Loop through the POD's to find PSO namespace and prefix and store pods with PVC's
        $images = [];
        foreach (($podList->items ?? []) as $item) {
            $myPod = null;
            $myPodName = $item->metadata->name ?? 'Unknown';
            $myPodNamespace = $item->metadata->namespace ?? 'Unknown';

            // Add a PsoPod for each pod with a PVC
            foreach (($item->spec->volumes ?? []) as $volume) {
                if ($volume->persistentVolumeClaim !== null) {
                    $myPod = new PsoPod($item->metadata->uid);
                    $myPod->name = $myPodName;
                    $myPod->namespace = $myPodNamespace;
                    $myPod->creationTimestamp = $item->metadata->creationTimestamp ?? '';
                    $myPod->status = $item->status->phase ?? '';

                    if ($myPod->labels == null) {
                        foreach (($item->metadata->labels ?? []) as $key => $value) {
                            $myPod->arrayPush('labels', $key . '=' . $value);
                        }
                    }
                    $containers = [];
                    foreach (($item->spec->containers ?? []) as $container) {
                        array_push(
                            $containers,
                            ($container->name ?? 'Unknown container name') . ': ' .
                            ($container->image  ?? 'Unknown container image')
                        );
                    }
                    $myPod->containers = $containers;

                    $myClaimName = ($volume->persistentVolumeClaim->claimName ?? 'Unknown');
                    $myPod->arrayPush('pvcName', $myClaimName);
                    $myNamespaceName = $myPod->namespace . ':' . $myClaimName;
                    $myPod->arrayPush('pvcNamespaceName', $myNamespaceName);
                }
            }

            foreach (($item->spec->containers ?? []) as $container) {
                foreach (($container->env ?? []) as $env) {
                    if (
                        // Provisioner POD name used for PSO6+
                        ($myPodName == 'pso-csi-controller-0')
                        // Provisioner POD name used for Flex or PSO5
                        or ($this->startsWith('pure-provisioner', $myPodName))
                        // Daemonset POD name used for PSO6+
                        or ($this->startsWith('pso-csi-node', $myPodName))
                        // Daemonset POD name used for PSO5
                        or ($this->startsWith('pure-csi-', $myPodName))
                        // Daemonset POD name used for Flex
                        or ($this->startsWith('pure-flex-', $myPodName))
                    ) {
                        switch ($env->name) {
                            case 'PURE_K8S_NAMESPACE':
                                // If PSO is found, set psoFound to true and store prefix and namespace in Redis
                                $this->psoFound = true;
                                $this->psoInfo->prefix = $env->value ?? 'Unknown';
                                $this->psoInfo->namespace = $myPodNamespace ?? 'Unknown';
                                break;
                            case 'PURE_DEFAULT_ENABLE_FB_NFS_SNAPSHOT':
                                $this->psoInfo->enableFbNfsSnapshot = $env->value ?? '';
                                break;
                            case 'PURE_FLASHARRAY_SAN_TYPE':
                                $this->psoInfo->sanType = $env->value ?? '';
                                break;
                            case 'PURE_DEFAULT_BLOCK_FS_TYPE':
                                $this->psoInfo->faDefaultFsType = $env->value ?? '';
                                break;
                            case 'PURE_DEFAULT_BLOCK_FS_OPT':
                                $this->psoInfo->faDefaultFSOpt = $env->value ?? '';
                                break;
                            case 'PURE_DEFAULT_BLOCK_MNT_OPT':
                                $this->psoInfo->faDefaultMountOpt = $env->value ?? '';
                                break;
                            case 'PURE_PREEMPT_RWO_ATTACHMENTS_DEFAULT':
                                $this->psoInfo->faPreemptAttachments = $env->value ?? '';
                                break;
                            case 'PURE_ISCSI_ALLOWED_CIDRS':
                                $this->psoInfo->faIscsiAllowedCidr = $env->value ?? '';
                                break;
                            case 'PURE_ISCSI_LOGIN_TIMEOUT':
                                $this->psoInfo->faIscsiLoginTimeout = $env->value ?? '';
                                break;
                        }
                    } elseif ($this->startsWith('pso-db-cockroach-operator', $myPodName)) {
                        switch ($env->name) {
                            case 'COCKROACH_MAX_STARTUP_SECONDS':
                                $this->psoInfo->dbMaxSuspectSeconds = $env->value ?? '';
                                break;
                            case 'COCKROACH_MAX_SUSPECT_SECONDS':
                                $this->psoInfo->dbMaxStartupSeconds = $env->value ?? '';
                                break;
                        }
                    }
                    if (
                        // Provisioner POD name used for PSO6+
                        ($this->startsWith('pso-csi-controller-0', $myPodName))
                        // Provisioner POD name used for PSO5
                        or ($this->startsWith('pure-provisioner', $myPodName))
                    ) {
                        if (!in_array($item->spec->nodeName, $this->psoInfo->psoNodes ?? [])) {
                            $this->psoInfo->psoProvisionerNode = $item->spec->nodeName;
                        }

                        if (array_key_exists('chart', $item->metadata->labels)) {
                            $this->psoInfo->helmChart = $item->metadata->labels['chart'];
                        }
                    }

                    if (
                        // Daemonset POD name used for PSO6+
                        ($this->startsWith('pso-csi-node', $myPodName))
                        // Daemonset POD name used for PSO5
                        or ($this->startsWith('pure-csi-', $myPodName))
                        // Daemonset POD name used for Flex
                        or ($this->startsWith('pure-flex-', $myPodName))
                    ) {
                        if (!in_array($item->spec->nodeName, $this->psoInfo->psoNodes ?? [])) {
                            $this->psoInfo->arrayPush('psoNodes', $item->spec->nodeName);
                        }
                    }
                    // psoProvisionerNode
                }

                if (
                    ($myPodName == 'pso-csi-controller-0')
                    or ($this->startsWith('pure-provisioner', $myPodName))
                ) {
                    $this->psoInfo->provisionerPod = $myPodName;
                    $this->psoInfo->provisionerLabels = $item->metadata->labels;
                    $this->psoInfo->provisionerContainer = $item->spec->containers[0]->name ?? 'Unknown';

                    $imageName = $item->spec->containers[0]->image;
                    $this->psoInfo->provisionerImage = explode(':', $imageName)[0] ?? 'unknown';
                    $this->psoInfo->provisionerTag = explode(':', $imageName)[1] ?? '0.0.0';

                    $majorRelease = intval(explode('.', str_ireplace('v', '', $this->psoInfo->provisionerTag))[0]);

                    if ($majorRelease < 5) {
                        $this->psoInfo->isCsiDriver = false;
                        $this->psoInfo->repoUri = env('FLEX_GITREPO', '/purestorage/helm-charts');
                        $this->psoInfo->valuesUri = env(
                            'FLEX_VALUES',
                            '/pure-k8s-plugin/values.yaml'
                        );
                        $this->psoInfo->psoEdition = 'FLEX';
                    } elseif ($majorRelease == 5) {
                        $this->psoInfo->isCsiDriver = true;
                        $this->psoInfo->repoUri = env('PSO5_GITREPO', '/purestorage/helm-charts');
                        $this->psoInfo->valuesUri = env(
                            'PSO5_VALUES',
                            '/pure-csi/values.yaml'
                        );
                        $this->psoInfo->psoEdition = 'PSO5';
                    } else {
                        $this->psoInfo->isCsiDriver = true;
                        $this->psoInfo->repoUri = env('PSO6_GITREPO', '/purestorage/pso-csi');
                        $this->psoInfo->valuesUri = env(
                            'PSO6_VALUES',
                            '/pure-pso/values.yaml'
                        );
                        $this->psoInfo->psoEdition = 'PSO6';
                    }

                    $myContainerName = $container->name ?? 'Unknown container name';
                    if (($myContainerName == 'pso-csi-container') or ($myContainerName == 'pure-csi-container')) {
                        array_push($images, $container->name . ': ' . $container->image);
                    }
                    if (($myContainerName == 'csi-provisioner') or ($myContainerName == 'pure-provisioner')) {
                        array_push($images, $container->name . ': ' . $container->image);
                        foreach (($container->args ?? []) as $arg) {
                            Log::debug($arg);
                            switch ($arg) {
                                case '--feature-gates=Topology=true':
                                    Log::debug('psoStorageTopology = true');
                                    $this->psoInfo->psoStorageTopology = 'true';
                                    break;
                                case '--feature-gates=Topology=false':
                                    Log::debug('psoStorageTopology = false');
                                    $this->psoInfo->psoStorageTopology = 'false';
                                    break;
                                case '--strict-topology':
                                    Log::debug('stricttopology = true');
                                    $this->psoInfo->psoStrictTopology = 'true';
                                    break;
                            }
                        }
                    }
                    if ($myContainerName == 'csi-snapshotter') {
                        array_push($images, $container->name . ': ' . $container->image);
                    }
                    if ($myContainerName == 'csi-attacher') {
                        array_push($images, $container->name . ': ' . $container->image);
                    }
                    if ($myContainerName == 'csi-resizer') {
                        array_push($images, $container->name . ': ' . $container->image);
                    }
                    if ($myContainerName == 'liveness-probe') {
                        array_push($images, $container->name . ': ' . $container->image);
                    }
                } elseif ($this->startsWith('pso-csi-node', $myPodName)) {
                    $line = $container->name . ': ' . $container->image;
                    if (!in_array($line, $images)) {
                        array_push($images, $line);
                    }
                } elseif ($this->startsWith('pso-db-', $myPodName)) {
                    $line = $container->name . ': ' . $container->image;
                    if (!in_array($line, $images)) {
                        array_push($images, $line);
                    }
                    foreach(($item->spec->initContainers ?? []) as $initContainer) {
                        $line = $initContainer->name . ': ' . $initContainer->image;
                        if (!in_array($line, $images)) {
                            array_push($images, $line);
                        }
                    }
                }
            }
        }
        $this->psoInfo->images = $images;

        if (file_exists('/run/secrets/rhsm')) {
            $this->psoInfo->isOpenShift = true;
        } else {
            $this->psoInfo->isOpenShift = false;
        }

        if ($this->psoInfo->namespace !== null) {
            try {
                Client::configure($this->master, $this->authentication, ['timeout' => 10]);
                $configMap = new ConfigMap();
                $configMapList = $configMap->list($this->psoInfo->namespace);

                $this->psoInfo->nfsExportRules = '*(rw,no_root_squash)';
                foreach ($configMapList->items as $item) {
                    if ($item->metadata->name == 'pure-csi-container-configmap') {
                        foreach ($item->data as $filename => $json) {
                            $data = json_decode($json, true);

                            foreach ($data as $key => $value) {
                                switch ($key) {
                                    case 'exportRules':
                                        $this->psoInfo->nfsExportRules = $value ?? '*(rw,no_root_squash)';
                                        break;
                                }
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                // Log error message4
                $message = '    Unable to retrieving PSO configmap, ' .
                    'for PSO 5.x and earlier this message can be ignored.';
                Log::warning($message);
                Log::debug('      - Message: "' . $e->getMessage() . '"');
                Log::debug('      - File: "' . $e->getFile() . '"');
                Log::debug('      - Line: "' . $e->getLine() . '"');
                unset($e);
            }
        }

        if (!$this->psoFound) {
            // Log error message
            Log::error('  xxx Error unable to find PSO instance "' . $this->master . '"');

            // If PSO was not found, return an error
            $this->errorSource = 'pso';
            $this->errorMessage = 'Unable to find PSO namespace';
            return false;
        } else {
            return true;
        }
    }

    /**
     * Connect to Kubernetes to retrieve the StorageClasses
     *
     * @return boolean
     */
    private function getStorageClasses()
    {
        // Log function call
        Log::info('       Call getStorageClasses()');

        // Retrieve all Kubernetes StorageClasses for this cluster
        Client::configure($this->master, $this->authentication);
        $storageclass = new StorageClass();
        $storageclassList = $storageclass->list();

        if (isset($storageclassList->code)) {
            $this->psoFound = false;
            $this->errorSource = 'k8s';
            $this->errorMessage = 'Unable to list StorageClasses. Check the ClusterRoles and ClusterRoleBindings.';
            return false;
        }

        foreach (($storageclassList->items ?? []) as $item) {
            // Add all storageclasses that use PSO
            if (in_array(($item->provisioner ?? ''), self::PURE_PROVISIONERS)) {
                $mystorageclass = new PsoStorageClass($item->metadata->name ?? $item->metadata->uid);

                $parameters = [];
                foreach (($item->parameters ?? []) as $key => $value) {
                    array_push($parameters, $key . '=' . $value);
                    if ($key == 'backend') {
                        $mystorageclass->backend = $value;
                    }
                }
                $mystorageclass->parameters = $parameters;

                foreach (($item->mountOptions ?? []) as $key => $value) {
                    // key is only the array counter
                    $mystorageclass->arrayPush('mountOptions', $value);
                }

                $mystorageclass->allowVolumeExpansion = $item->allowVolumeExpansion ?? '';
                $mystorageclass->volumeBindingMode = $item->volumeBindingMode ?? '';
                $mystorageclass->reclaimPolicy = $item->reclaimPolicy ?? '';

                $mystorageclass->isDefaultClass = false;
                foreach (($item->metadata->annotations ?? []) as $key => $value) {
                    if (('storageclass.kubernetes.io/is-default-class' == $key) and ($value == 'true')) {
                        $mystorageclass->isDefaultClass = true;
                    }
                }

                $terms = [];
                foreach (($item->allowedTopologies ?? []) as $index1 => $allowedTopology) {
                    $expressions = [];
                    foreach (($allowedTopology->matchLabelExpressions ?? []) as $index2 => $matchLabelExpression) {
                        $expressions[$index2] = $matchLabelExpression->key . ' in [' .
                            implode(',', $matchLabelExpression->values) . ']';
                    }
                    $terms[$index1] = 'Term ' . $index1 . ': ' . implode(', ', $expressions);
                }
                $mystorageclass->allowedTopologies = $terms;
            }
        }
        return true;
    }

    /**
     * Connect to Kubernetes to retrieve the PersistentVolumes
     *
     * @return boolean
     */
    private function getPersistentVolumes()
    {
        // Log function call
        Log::info('       Call getPersistentVolumes()');

        // Retrieve all Kubernetes PVC's for this cluster
        Client::configure($this->master, $this->authentication);
        $pv = new PersistentVolume();
        $pvList = $pv->list();

        if (isset($pvList->code)) {
            $this->psoFound = false;
            $this->errorSource = 'k8s';
            $this->errorMessage = 'Unable to list Persistent Volumes (PV\'s). ' .
                'Check the ClusterRoles and ClusterRoleBindings.';
            return false;
        }

        $pureStorageClasses = PsoStorageClass::items(PsoStorageClass::PREFIX, 'name');
        foreach (($pvList->items ?? []) as $item) {
            // Only use PVs that are managed by PSO
            if (in_array($item->spec->storageClassName, $pureStorageClasses)) {
                $name = $item->metadata->name ?? '';
                if ($name !== '') {
                    // Create PV record by PV name
                    $newPv = new PsoPersistentVolume($name);

                    // Save metadata fields
                    $newPv->creationTimestamp = $item->metadata->creationTimestamp ?? null;

                    // Save latest update time field from managedFields for csi-provisioner
                    foreach ($item->metadata->managedFields as $managedField) {
                        if ($managedField->manager == 'csi-provisioner') {
                            $newPv->updateTimestamp = $managedField->time;
                        }
                    }
                    $newPv->finalizers = $item->metadata->finalizers ?? [];
                    $newPv->resourceVersion = $item->metadata->resourceVersion ?? null;
                    $newPv->uid = $item->metadata->uid ?? null;

                    // Save spec fields
                    if (is_array($item->spec->accessModes) and count($item->spec->accessModes) > 0) {
                        $newPv->accessModes = $item->spec->accessModes[0];
                    } else {
                        $newPv->accessModes = 'Unknown';
                    }
                    $newPv->capacity = $item->spec->capacity['storage'] ?? null;
                    foreach (($item->metadata->labels ?? []) as $key => $value) {
                        $newPv->arrayPush('labels', $key . '=' . $value);
                    }
                    $newPv->persistentVolumeReclaimPolicy = $item->spec->persistentVolumeReclaimPolicy ?? null;
                    $newPv->storageClassName = $item->spec->storageClassName ?? null;
                    $newPv->volumeMode = $item->spec->volumeMode ?? null;

                    // spec->csi fields
                    $newPv->csi_backend = $item->spec->csi->volumeAttributes['backend'] ?? null;
                    $newPv->csi_createoptions = $item->spec->csi->volumeAttributes['createoptions'] ?? null;
                    $newPv->csi_driver = $item->spec->csi->driver ?? null;
                    $newPv->csi_fsType = $item->spec->csi->fsType ?? null;
                    $newPv->csi_namespace = $item->spec->csi->volumeAttributes['namespace'] ?? null;
                    $newPv->csi_volumeHandle = $item->spec->csi->volumeHandle ?? null;
                    $newPv->csi_volumeName = $item->spec->csi->volumeAttributes['volumeName'] ?? null;

                    // spec->status fields
                    $newPv->status_message = $item->status->message ?? null;
                    $newPv->status_phase = $item->status->phase ?? null;
                    $newPv->status_reason = $item->status->reason ?? null;

                    // spec->claimRef fields
                    $newPv->claimRef_name = $item->spec->claimRef->name ?? '';
                    $newPv->claimRef_namespace = $item->spec->claimRef->namespace ?? '';
                    $newPv->claimRef_resourceVersion = $item->spec->claimRef->resourceVersion ?? '';
                    $newPv->claimRef_uid = $item->spec->claimRef->uid ?? '';

                    // Calculated data fields
                    if (($newPv->status_phase == 'Released') or ($newPv->status_phase == 'Available')) {
                        $newPv->isReleased = $name;
                    }
                }
            }
        }
        return true;
    }

    /**
     * Connect to Kubernetes to retrieve the PersistentVolumeClaims
     *
     * @return boolean
     */
    private function getPersistentVolumeClaims()
    {
        // Log function call
        Log::info('       Call getPersistentVolumeClaims()');

        // Retrieve all Kubernetes PVC's for this cluster
        Client::configure($this->master, $this->authentication);
        $pvc = new PersistentVolumeClaim();
        $pvcList = $pvc->list('');

        if (isset($pvcList->code)) {
            $this->psoFound = false;
            $this->errorSource = 'k8s';
            $this->errorMessage = 'Unable to list Persistent Volume Claims. ' .
                'Check the ClusterRoles and ClusterRoleBindings.';
            return false;
        }

        $pureStorageClasses = PsoStorageClass::items(PsoStorageClass::PREFIX, 'name');
        foreach (($pvcList->items ?? []) as $item) {
            $storageProvisioner = $item->metadata->annotations['volume.beta.kubernetes.io/storage-provisioner']
                ?? 'unknown';

            if (
                in_array($item->spec->storageClassName, $pureStorageClasses) or
                in_array($storageProvisioner, self::PURE_PROVISIONERS)
            ) {
                $uid = $item->metadata->uid ?? '';
                if ($uid !== '') {
                    // Create PV record by PV name
                    $newPvc = new PsoPersistentVolumeClaim($uid);

                    // Save metadata fields
                    $newPvc->annotations = [];
                    foreach (($item->metadata->annotations ?? []) as $key => $value) {
                        $newPvc->arrayPush('annotations', $key . '=' . $value);
                    }
                    $newPvc->creationTimestamp = $item->metadata->creationTimestamp ?? null;
                    $newPvc->finalizers = $item->metadata->finalizers ?? [];
                    foreach (($item->metadata->labels ?? []) as $key => $value) {
                        $newPvc->arrayPush('labels', $key . '=' . $value);
                    }
                    $newPvc->name = $item->metadata->name ?? 'Unknown';
                    $newPvc->namespace = $item->metadata->namespace ?? 'Unknown';
                    $newPvc->resourceVersion = $item->metadata->resourceVersion ?? null;

                    // spec fields
                    if (is_array($item->spec->accessModes) and count($item->spec->accessModes) > 0) {
                        $newPvc->accessModes = $item->spec->accessModes[0];
                    } else {
                        $newPvc->accessModes = 'Unknown';
                    }
                    $newPvc->storageClassName = $item->spec->storageClassName ?? null;
                    $newPvc->volumeMode = $item->spec->volumeMode ?? null;
                    $newPvc->volumeName = $item->spec->volumeName ?? null;

                    // spec->status fields
                    if (is_array($item->status->accessModes) and count($item->status->accessModes) > 0) {
                        $newPvc->status_accessModes = $item->status->accessModes[0];
                    } else {
                        $newPvc->status_accessModes = null;
                    }
                    $newPvc->status_capacity = $item->status->capacity['storage'] ?? null;
                    $newPvc->status_conditions = $item->status->conditions ?? null;
                    $newPvc->status_phase = $item->status->phase ?? null;

                    // Calculated data fields
                    $newPvc->namespaceName = $newPvc->namespace . ':' . $newPvc->name;
                }
            }
        }
        return true;
    }

    /**
     * Connect to Kubernetes to retrieve the Statefulsets
     *
     * @return boolean
     */
    private function getStatefulsets()
    {
        // Log function call
        Log::info('       Call getStatefulsets()');

        // Retrieve all Kubernetes StatefulSets for this cluster
        Client::configure($this->master, $this->authentication);
        $statefulset = new StatefulSet();
        $statefulsetList = $statefulset->list('');

        if (isset($statefulsetList->code)) {
            $this->psoFound = false;
            $this->errorSource = 'k8s';
            $this->errorMessage = 'Unable to list StatefulSets. Check the ClusterRoles and ClusterRoleBindings.';
            return false;
        }

        $pureStorageClasses = PsoStorageClass::items(PsoStorageClass::PREFIX, 'name');
        $listPvc_namespaceName = PsoPersistentVolumeClaim::items(PsoPersistentVolumeClaim::PREFIX, 'namespaceName');
        foreach (($statefulsetList->items ?? []) as $item) {
            // Only check statefulsets that use a volumeclaimtemplate
            if (isset($item->spec->volumeClaimTemplates)) {
                // Get all volumes for statefulset, using namespaceNames format
                $namespaceNames = [];
                foreach (($item->spec->volumeClaimTemplates ?? []) as $volumeClaimTemplate) {
                    for ($i = 0; $i < $item->spec->replicas; $i++) {
                        $pvcNamespaceName = $item->metadata->namespace . ':' .
                        $volumeClaimTemplate->metadata->name . '-' . $item->metadata->name . '-' . $i;

                        // Add volume name to $namespaceNames, if the statefulset volume is in our PVC list
                        if (in_array($pvcNamespaceName, $listPvc_namespaceName)) {
                            array_push($namespaceNames, $pvcNamespaceName);
                        }
                    }
                }

                // If the stateful set contains one or mode PSO volumes, save a record
                if ($namespaceNames !== []) {
                    $myset = new PsoStatefulSet($item->metadata->uid);
                    $myset->name = $item->metadata->name ?? 'Unknown';
                    $myset->namespace = $item->metadata->namespace ?? 'Unknown';
                    $myset->namespaceNames = $namespaceNames;
                    $myset->creationTimestamp = $item->metadata->creationTimestamp ?? '';
                    $myset->replicas = $item->spec->replicas ?? '';

                    foreach (($item->metadata->labels ?? []) as $key => $value) {
                        $myset->arrayPush('labels', $key . '=' . $value);
                    }
                }
            }
        }
        return true;
    }

    /**
     * Connect to Kubernetes to retrieve the Deployments
     *
     * @return boolean
     */
    private function getDeployments()
    {
        // Log function call
        Log::info('       Call getDeployments()');

        // Retrieve all Kubernetes StatefulSets for this cluster
        Client::configure($this->master, $this->authentication);
        $deployment = new Deployment();
        $deploymentList = $deployment->list('');

        if (isset($deploymentList->code)) {
            $this->psoFound = false;
            $this->errorSource = 'k8s';
            $this->errorMessage = 'Unable to list Deployments. Check the ClusterRoles and ClusterRoleBindings.';
            return false;
        }

        $listPvc_namespaceName = PsoPersistentVolumeClaim::items(PsoPersistentVolumeClaim::PREFIX, 'namespaceName');
        foreach (($deploymentList->items ?? []) as $item) {
            foreach (($item->spec->template->spec->volumes ?? []) as $vol) {
                // Only check deployments that use persistent volume claims
                if (isset($vol->persistentVolumeClaim->claimName)) {
                    $mynamespaceName = ($item->metadata->namespace ?? 'Unknown') . ':' .
                        ($vol->persistentVolumeClaim->claimName  ?? 'Unknown');

                    if (in_array($mynamespaceName, $listPvc_namespaceName)) {
                        $mydeployment = new PsoDeployment($item->metadata->uid);
                        $mydeployment->name = $item->metadata->name ?? 'Unknown';
                        $mydeployment->namespace = $item->metadata->namespace ?? 'Unknown';
                        $mydeployment->creationTimestamp = $item->metadata->creationTimestamp ?? '';
                        $mydeployment->volumeCount = $mydeployment->volumeCount + 1;
                        $mydeployment->replicas = $item->spec->replicas ?? '';
                        $mydeployment->arrayPush('namespaceNames', $mynamespaceName);

                        $labels = [];
                        foreach (($item->metadata->labels ?? []) as $key => $value) {
                            array_push($labels, $key . '=' . $value);
                        }
                        $mydeployment->labels = $labels;
                    }
                }
            }
        }
        return true;
    }

    /**
     * Connect to Kubernetes to retrieve the Jobs
     *
     * @return boolean
     */
    private function getJobs()
    {
        // Log function call
        Log::info('       Call getJobs()');

        // Retrieve all Kubernetes StatefulSets for this cluster
        Client::configure($this->master, $this->authentication);
        $job = new Job();
        $jobList = $job->list('');

        if (isset($jobList->code)) {
            $this->psoFound = false;
            $this->errorSource = 'k8s';
            $this->errorMessage = 'Unable to list Jobs. Check the ClusterRoles and ClusterRoleBindings.';
            return false;
        }

        $listPvc_namespaceName = PsoPersistentVolumeClaim::items(PsoPersistentVolumeClaim::PREFIX, 'namespaceName');
        foreach (($jobList->items ?? []) as $item) {
            foreach (($item->spec->template->spec->volumes ?? []) as $volume) {
                $mynamespaceName = ($item->metadata->namespace ?? 'Unknown') . ':' .
                    ($volume->persistentVolumeClaim->claimName ?? 'Unknown');
                if (in_array($mynamespaceName, $listPvc_namespaceName)) {
                    $myJob = new PsoJob($item->metadata->uid);
                    $myJob->name = $item->metadata->name ?? 'Unknown';
                    $myJob->namespace = $item->metadata->namespace ?? 'Unknown';
                    $myJob->creationTimestamp = $item->metadata->creationTimestamp ?? '';

                    if ($item->status->active == 1) {
                        $myJob->status = 'Running';
                    } elseif ($item->status->succeeded == 1) {
                        $myJob->status = 'Completed';
                    } elseif ($item->status->failed == 1) {
                        $myJob->status = 'Failed';
                    } else {
                        $myJob->status = 'Unknown';
                    }

                    if ($myJob->labels == null) {
                        foreach (($item->metadata->labels ?? []) as $key => $value) {
                            $myJob->arrayPush('labels', $key . '=' . $value);
                        }
                    }

                    $myJob->arrayPush('pvcName', $volume->persistentVolumeClaim->claimName ?? 'not set');
                    $myJob->arrayPush(
                        'pvcNamespaceName',
                        ($item->metadata->namespace ?? 'Unknown') . ':' .
                        ($volume->persistentVolumeClaim->claimName ?? 'Unknown')
                    );
                }
            }
        }
        return true;
    }

    /**
     * Connect to Kubernetes to retrieve the VolumeSnapshotClasses
     * this set is optional, since not all supported k8s cluster
     * support VolumeSnapshots by default
     *
     * @return boolean
     */
    public function getVolumeSnapshotClasses()
    {
        // Log function call
        Log::info('       Call getVolumeSnapshotClasses()');

        // Get API version for snapshot.storage.k8s.io
        $this->psoInfo->snapshotApiVersion = '';
        Client::configure($this->master, $this->authentication, ['timeout' => 10]);
        $api = new APIService();
        $apiList = $api->list();

        foreach (($apiList->items ?? []) as $apiResource) {
            if ($apiResource->spec->group == 'snapshot.storage.k8s.io') {
                // Get API version (v1alpha1 or v1beta1) for the `snapshot.storage.k8s.io` API
                $this->psoInfo->snapshotApiVersion = $apiResource->spec->version ?? 'Unknown';
            }
        }
        Client::configure($this->master, $this->authentication, ['timeout' => 10]);
        $class = new VolumeSnapshotClass();

        try {
            if ($this->psoInfo->snapshotApiVersion == 'v1alpha1') {
                $snapshotterName = 'snapshotter';
                $reclaimName = 'reclaimPolicy';
                $classList = $class->listV1alpha1();
            } else {
                // Default to v1beta1
                $snapshotterName = 'driver';
                $reclaimName = 'deletionPolicy';
                $classList = $class->listV1beta1();
            }

            if (isset($classList->code)) {
                // If we cannot select the API version or an error is returned, we will abort
                // However we will not return an error, since snapshots suppport is optional
                Log::warning('xxx Unable to access the VolumeSnapshotClasses API.');
                return false;
            }

            foreach (($classList['items'] ?? []) as $item) {
                if (in_array($item[$snapshotterName], self::PURE_PROVISIONERS)) {
                    $snapshotclass = new PsoVolumeSnapshotClass($item['metadata']['name'] ?? 'Unknown');

                    $snapshotclass->snapshotter = $item[$snapshotterName] ?? '';
                    $snapshotclass->reclaimPolicy = $item[$reclaimName] ?? '';

                    $snapshotclass->isDefaultClass = false;
                    $annotaionName = 'snapshot.storage.kubernetes.io/is-default-class';
                    if (isset($item['metadata']['annotations'][$annotaionName])) {
                        if ($item['metadata']['annotations'][$annotaionName] == 'true') {
                            $snapshotclass->isDefaultClass = true;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // Log error message
            Log::warning(
                'xxx Error retrieving VolumeSnapshotClasses using API version ' .
                $this->psoInfo->snapshotApiVersion
            );
            Log::debug('      - Message: "' . $e->getMessage() . '"');
            Log::debug('      - File: "' . $e->getFile() . '"');
            Log::debug('      - Line: "' . $e->getLine() . '"');
            unset($e);
        }
    }

    /**
     * Connect to Kubernetes to retrieve the VolumeSnapshots
     * this set is optional, since not all supported k8s cluster
     * support VolumeSnapshots by default
     *
     * @return boolean
     */
    public function getVolumeSnapshots()
    {
        // Log function call
        Log::info('       Call getVolumeSnapshots()');

        Client::configure($this->master, $this->authentication, ['timeout' => 10]);
        $snap = new VolumeSnapshot();


        try {
            if ($this->psoInfo->snapshotApiVersion == 'v1alpha1') {
                $snapList = $snap->listV1alpha1('');
            } else {
                $snapList = $snap->listV1beta1('');
            }

            if (isset($snapList->code)) {
                // Do not return an error if not found, since this is a feature gate that might not be enabled.
                return false;
            }

            foreach (($snapList['items'] ?? []) as $item) {
                if ($this->psoInfo->snapshotApiVersion == 'v1alpha1') {
                    $volumeSnapshotClassName = $item['spec']['snapshotClassName'] ?? '';
                } else {
                    $volumeSnapshotClassName = $item['spec']['volumeSnapshotClassName'] ?? '';
                }
                $myStorageClasses = PsoVolumeSnapshotClass::items(PsoVolumeSnapshotClass::PREFIX, 'name');
                if (in_array($volumeSnapshotClassName, $myStorageClasses)) {
                    $volumeSnapshot = new PsoVolumeSnapshot($item['metadata']['uid'] ?? 'Unknown');

                    $volumeSnapshot->name = $item['metadata']['name'] ?? '';
                    $volumeSnapshot->namespace = $item['metadata']['namespace'] ?? '';
                    $volumeSnapshot->creationTimestamp = $item['metadata']['creationTimestamp'] ?? '';
                    $volumeSnapshot->snapshotClassName = $volumeSnapshotClassName ?? '';
                    $volumeSnapshot->creationTime = $item['status']['creationTime'] ?? '';
                    $volumeSnapshot->readyToUse = $item['status']['readyToUse'] ?? 'Pending';

                    if ($this->psoInfo->snapshotApiVersion == 'v1alpha1') {
                        $volumeSnapshot->snapshotContentName = $item['spec']['snapshotContentName'] ?? 'Unknown';
                        $volumeSnapshot->sourceName = $item['spec']['source']['name'] ?? 'Unknown';
                        $volumeSnapshot->sourceKind = $item['spec']['source']['kind'] ?? 'Persistent Volume Claim';
                    } else {
                        $volumeSnapshot->snapshotContentName =
                            $item['status']['boundVolumeSnapshotContentName'] ?? 'Unknown';
                        $volumeSnapshot->sourceName = $item['spec']['source']['persistentVolumeClaimName'] ??
                            'Unknown';
                        $volumeSnapshot->sourceKind = 'Persistent Volume Claim';
                    }

                    foreach (($item['status']['error'] ?? []) as $key => $value) {
                        switch ($key) {
                            case 'message':
                                $volumeSnapshot->errorMessage = $value;
                                break;
                            case 'time':
                                $volumeSnapshot->errorTime = $value;
                                break;
                        }
                    }

                    $uid = PsoPersistentVolumeClaim::getUidByNamespaceName(
                        $volumeSnapshot->namespace,
                        $volumeSnapshot->sourceName
                    );
                    if ($uid !== null) {
                        $myPvc = new PsoPersistentVolumeClaim($uid);
                        $myPvc->hasSnaps = true;
                    }
                }
            }
        } catch (Exception $e) {
            // Log error message
            Log::warning(
                'xxx Error retrieving VolumeSnapshots using API version ' .
                $this->psoInfo->snapshotApiVersion
            );
            Log::debug('      - Message: "' . $e->getMessage() . '"');
            Log::debug('      - File: "' . $e->getFile() . '"');
            Log::debug('      - Line: "' . $e->getLine() . '"');
            unset($e);
        }
    }

    /**
     * Connect to the Pure Storage® arrays (FlashArray™ and FlashBlade®) to retrieve
     * management IP's and API tokens
     *
     * @return boolean
     */
    private function getArrayInfo()
    {
        // Log function call
        Log::info('       Call getArrayInfo()');

        // Get the PSO secret from Kubernetes to retrieve the MgmtEndPoint and APIToken
        Client::configure($this->master, $this->authentication);
        $secret = new Secret($this->psoInfo->namespace);

        $psoConfig = $secret->read($this->psoInfo->namespace, 'pure-provisioner-secret');
        if (isset($psoConfig->code)) {
            $this->psoFound = false;
            $this->errorSource = 'k8s';
            $this->errorMessage = 'Unable to read (get) the PSO secret. ' .
                'Check the ClusterRoles and ClusterRoleBindings.';
            return false;
        }

        if (isset($psoConfig->data)) {
            $psoSecretData = $psoConfig->data;
            $psoConfig = json_decode(base64_decode($psoSecretData['pure.json'], true));
        } else {
            $this->psoFound = false;
            $this->errorSource = 'k8s';
            $this->errorMessage = 'Unexpected error while getting PSO secrets. ' .
                'Check the ClusterRoles and ClusterRoleBindings.';
            return false;
        }

        $psoYaml = $this->objectToArray($psoConfig);
        if ($psoYaml !== []) {
            $myYaml = [];
            foreach ($psoYaml as $item) {
                $myYaml = array_merge($myYaml, $item);
            }
            $this->psoInfo->yaml = yaml_emit(["arrays" => $myYaml]);
        }

        // Get FlashArray™ information
        foreach (($psoConfig->FlashArrays ?? []) as $flasharray) {
            $mgmtEndPoint = $flasharray->MgmtEndPoint ?? 'not set';
            $apiToken = $flasharray->APIToken ?? 'not set';

            if (($mgmtEndPoint !== 'not set') and ($apiToken !== 'not set')) {
                $newArray = new PsoArray($mgmtEndPoint);
                $newArray->apiToken = $apiToken;
                foreach (($flasharray->Labels ?? []) as $key => $value) {
                    $newArray->arrayPush('labels', $key . '=' . $value);
                }

                $fa = new FlashArrayApi();
                try {
                    // Connect to the array for the array name
                    $fa->authenticate($mgmtEndPoint, $apiToken);
                    $arrayDetails = $fa->getArray();
                    $modelDetails = $fa->getArray('controllers=true');

                    $newArray->name = $arrayDetails['array_name'];
                    $newArray->version = 'Purity//FA ' . $arrayDetails['version'];
                    $newArray->model = 'Pure Storage® FlashArray™ ' . $modelDetails[0]['model'];
                    $newArray->flasharray = 'flasharray';

                    $portDetails = $fa->getPort();

                    foreach (($portDetails  ?? []) as $portDetail) {
                        if (isset($portDetail['portal'])) {
                            $portal = explode(':', $portDetail['portal']);
                            if (count($portal) == 2) {
                                $ip = $portal[0];
                                //$port = $portal[1];
                                $newArray->arrayPush('iSCSIEndpoints', $ip);
                            }
                        }

                        if (isset($portDetail['iqn']) and !in_array('iSCSI', ($newArray->protocols ?? []))) {
                            $newArray->arrayPush('protocols', 'iSCSI');
                        }
                        if (isset($portDetail['wwn']) and !in_array('FC', ($newArray->protocols ?? []))) {
                            $newArray->arrayPush('protocols', 'FC');
                        }
                        if (isset($portDetail['nqn']) and !in_array('NVMe', ($newArray->protocols ?? []))) {
                            $newArray->arrayPush('protocols', 'NVMe');
                        }
                    }
                } catch (Exception $e) {
                    // Log error message
                    Log::error('  xxx Error connecting to FlashArray™ "' . $mgmtEndPoint . '"');
                    Log::debug('      - Message: "' . $e->getMessage() . '"');
                    Log::debug('      - File: "' . $e->getFile() . '"');
                    Log::debug('      - Line: "' . $e->getLine() . '"');

                    $newArray->name = $mgmtEndPoint;
                    $newArray->model = 'Unknown';
                    $newArray->offline = $mgmtEndPoint;
                    $newArray->message = 'Unable to connect to FlashArray™ (' . $e->getMessage() . ')';
                    unset($e);
                }
            } else {
                if ($mgmtEndPoint !== 'not set') {
                    $newArray = new PsoArray($mgmtEndPoint);
                    $newArray->name = $mgmtEndPoint;
                    $newArray->model = 'Unknown';
                    $newArray->offline = $mgmtEndPoint;
                    $newArray->message = 'No API token was set for this FlashArray™. ' .
                        'Please check the PSO configurations (values.yaml).';
                }
            }
        }

        // Get FlashBlade® information
        foreach (($psoConfig->FlashBlades ?? []) as $flashblade) {
            $mgmtEndPoint = $flashblade->MgmtEndPoint ?? 'not set';
            $apiToken = $flashblade->APIToken ?? 'not set';
            $nfsEndPoint = $flashblade->NFSEndPoint ?? 'not set';

            if (($mgmtEndPoint !== 'not set') and ($apiToken !== 'not set')) {
                $newArray = new PsoArray($mgmtEndPoint);
                $newArray->apiToken = $apiToken;
                $myLabels = [];
                foreach (($flashblade->Labels  ?? []) as $key => $value) {
                    array_push($myLabels, $key . '=' . $value);
                }
                $newArray->labels = $myLabels;

                $fb = new FlashBladeApi($mgmtEndPoint, $apiToken);
                try {
                    // Connect to the array for the array name
                    $fb->authenticate();
                    $array = $fb->getArray();

                    $newArray->name = $array['items'][0]['name'];
                    $newArray->model = 'Pure Storage® FlashBlade®';
                    $newArray->version = $array['items'][0]['os'] . ' ' . $array['items'][0]['version'];
                    $newArray->protocols = ['NFS', 'S3'];
                    $newArray->flashblade = 'flashblade';
                } catch (Exception $e) {
                    // Log error message
                    Log::error('  xxx Error connecting to FlashBlade® "' . $mgmtEndPoint . '"');
                    Log::debug('      - Message: "' . $e->getMessage() . '"');
                    Log::debug('      - File: "' . $e->getFile() . '"');
                    Log::debug('      - Line: "' . $e->getLine() . '"');

                    $newArray->name = $mgmtEndPoint;
                    $newArray->model = 'Offline';
                    $newArray->offline = $mgmtEndPoint;
                    $newArray->message = 'Unable to connect to FlashBlade® (' . $e->getMessage() . ')';
                    unset($e);
                }

                if ($nfsEndPoint == 'not set') {
                    if (isset($flashblade->NfsEndPoint)) {
                        $newArray->message = 'Currently using NfsEndPoint for this FlashBlade®. ' .
                            'Please change your values.yaml to use NFSEndPoint, as NfsEndPoint is deprecated.';
                        $newArray->arrayPush('nfsEndpoints', $flashblade->NfsEndPoint);
                    } else {
                        $newArray->message = 'No NFSEndPoint was set for this FlashBlade®. ' .
                            'Please check the PSO configurations (values.yaml).';
                    }
                } else {
                    $newArray->arrayPush('nfsEndpoints', $nfsEndPoint);
                }
            } else {
                if ($mgmtEndPoint !== 'not set') {
                    $newArray = new PsoArray($mgmtEndPoint);
                    $newArray->name = $mgmtEndPoint;
                    $newArray->model = 'Unknown';
                    $newArray->offline = $mgmtEndPoint;
                    if ($apiToken == 'not set') {
                        $newArray->message = 'No API token was set for this FlashBlade®. ' .
                            'Please check the PSO configurations (values.yaml).';
                    }
                }
            }
        }

        if (count(PsoArray::items(PsoArray::PREFIX, 'mgmtEndPoint')) == 0) {
            // If no arrays were found, return an error
            $this->psoFound = false;
            $this->errorSource = 'pso';
            $this->errorMessage = 'No arrays configured for PSO! Check the syntax of your values.yaml file.';
            return false;
        };

        return true;
    }

    /**
     * Connect to the Pure Storage® arrays to retrieve volume information
     *
     * @return boolean
     */
    private function addArrayVolumeInfo()
    {
        // Log function call
        Log::info('       Call addArrayVolumeInfo()');

        // Initialize variables
        $this->psoInfo->totalUsed = 0;
        $this->psoInfo->totalSize = 0;
        $this->psoInfo->totalOrphanedUsed = 0;
        $this->psoInfo->totalSnapshotUsed = 0;
        $this->psoInfo->totalIopsRead = 0;
        $this->psoInfo->totalIopsWrite = 0;
        $this->psoInfo->totalBwRead = 0;
        $this->psoInfo->totalBwWrite = 0;

        foreach (PsoArray::items(PsoArray::PREFIX, 'mgmtEndPoint') as $item) {
            $array = new PsoArray($item);
            Log::info('       Array: ' . $array->mgmtEndPoint);

            if (strpos($array->model, 'FlashArray') and ($array->offline == null)) {
                $this->addFlashArrayVolInfo($array, $this->psoInfo->prefix . '-*');

                $this->addFlashArrayPerfInfo($array, $this->psoInfo->prefix . '-pvc-*');

                $this->addFlashArrayHistInfo($array, $this->psoInfo->prefix . '-pvc-*');

                $this->addFlashArraySnapInfo($array, $this->psoInfo->prefix . '-pvc-*');
            } elseif (strpos($array->model, 'FlashBlade') and ($array->offline == null)) {
                $this->addFlashBladeVolInfo($array, $this->psoInfo->prefix . '-*');
            }
        }
    }

    /**
     * Connect to the Pure Storage® arrays to retrieve missing volume information
     *
     * @return boolean
     */
    private function addImportedVolumeInfo()
    {
        // Log function call
        Log::info('       Call addImportedVolumeInfo()');

        $pureStorageClasses = PsoStorageClass::items(PsoStorageClass::PREFIX, 'name');

        foreach (PsoPersistentVolume::items(PsoPersistentVolume::PREFIX, 'name') as $name) {
            $myPv = new PsoPersistentVolume($name);
            if (($myPv->pureName == null) and ($myPv->storageClassName !== null)) {
                if (in_array($myPv->storageClassName, $pureStorageClasses)) {
                    $myStorageClass = new PsoStorageClass($myPv->storageClassName);

                    if ($myStorageClass->backend == 'block') {
                        foreach (PsoArray::items(PsoArray::PREFIX, 'mgmtEndPoint') as $mgmtEndPoint) {
                            $array = new PsoArray($mgmtEndPoint);

                            if ($array->flasharray !== null) {
                                if ($this->addFlashArrayVolInfo($array, $myPv->csi_volumeHandle)) {
                                    $this->addFlashArrayPerfInfo($array, $myPv->csi_volumeHandle);

                                    $this->addFlashArrayHistInfo($array, $myPv->csi_volumeHandle);

                                    $this->addFlashArraySnapInfo($array, $myPv->csi_volumeHandle);

                                    break;
                                }
                            }
                        }
                    } elseif ($myStorageClass->backend == 'file') {
                        foreach (PsoArray::items(PsoArray::PREFIX, 'mgmtEndPoint') as $mgmtEndPoint) {
                            $array = new PsoArray($mgmtEndPoint);

                            if ($array->flashblade !== null) {
                                if ($this->addFlashBladeVolInfo($array, $myPv->csi_volumeHandle)) {
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Remove the redis key which is used to determine is the data is stale
     * does not include the actual refresh
     *
     * @return boolean
     */
    public static function requestRefresh()
    {
        Log::info('   --- Data refresh scheduled');
        Redis::del(self::VALID_PSO_DATA_KEY);
    }

    /**
     * Refresh the (cached) data if the data is no longer valid
     *
     * @return boolean
     */
    private function refreshData()
    {
        // Only refresh data if the redis data is stale
        try {
            if ((Redis::get(self::VALID_PSO_DATA_KEY) !== null)) {
                $this->psoFound = true;
                $this->errorSource = '';
                $this->errorMessage = '';
                return true;
            }
        } catch (Exception $e) {
            // Log error message
            Log::error('  xxx Error connecting to Redis');
            Log::debug('      - Message: "' . $e->getMessage() . '"');
            Log::debug('      - File: "' . $e->getFile() . '"');
            Log::debug('      - Line: "' . $e->getLine() . '"');

            // If we catch a CURL error, return an error message
            $this->errorSource = 'redis';
            $this->errorMessage = 'Redis: ' . $e->getMessage();
            unset($e);
            return false;
        }

        // Check if an update is already running
        if (Redis::get(self::PSO_UPDATE_KEY) !== null) {
            Log::notice(' --- Already busy with refresh');
            $this->psoFound = false;
            $this->errorSource = 'refresh';
            $this->errorMessage = null;
            return true;
        } else {
            Log::notice(' --- Start Refresh data');
            Redis::set(self::PSO_UPDATE_KEY, time());
            Redis::expire(self::PSO_UPDATE_KEY, 30);
        }

        // Remove stale PSO data from Redis
        Log::info('       Remove stale data');
        Redis::del(self::VALID_PSO_DATA_KEY);
        PsoArray::deleteAll(PsoArray::PREFIX);
        PsoBackendVolume::deleteAll(PsoBackendVolume::PREFIX);
        PsoDeployment::DeleteAll(PsoDeployment::PREFIX);
        PsoInformation::deleteAll(PsoInformation::PREFIX);
        PsoLabels::deleteAll(PsoLabels::PREFIX);
        PsoNamespace::deleteAll(PsoNamespace::PREFIX);
        PsoPersistentVolume::deleteAll(PsoPersistentVolume::PREFIX);
        PsoPersistentVolumeClaim::deleteAll(PsoPersistentVolumeClaim::PREFIX);
        PsoPod::deleteAll(PsoPod::PREFIX);
        PsoJob::deleteAll(PsoJob::PREFIX);
        PsoNode::deleteAll(PsoNode::PREFIX);
        PsoStatefulSet::DeleteAll(PsoStatefulSet::PREFIX);
        PsoStorageClass::deleteAll(PsoStorageClass::PREFIX);
        PsoVolumeSnapshotClass::deleteAll(PsoVolumeSnapshotClass::PREFIX);
        PsoVolumeSnapshot::deleteAll(PsoVolumeSnapshot::PREFIX);


        // Get the nodes, we call this first, so that we can show node info even if PSO is not found
        if (!$this->getNodes()) {
            Redis::del(self::PSO_UPDATE_KEY);
            return false;
        }

        // Get PSO namespace and prefix from Kubernetes
        if (!$this->getPsoDetails()) {
            Redis::del(self::PSO_UPDATE_KEY);
            return false;
        }

        // Get the storageclasses that use PSO
        if (!$this->getStorageClasses()) {
            Redis::del(self::PSO_UPDATE_KEY);
            return false;
        }

        // Get the persistent volume provisioned by PSO
        if (!$this->getPersistentVolumes()) {
            return false;
        }

        // Get the persistent volume claims provisioned by PSO
        if (!$this->getPersistentVolumeClaims()) {
            Redis::del(self::PSO_UPDATE_KEY);
            return false;
        }

        // Get the statefulsets
        if (!$this->getStatefulsets()) {
            Redis::del(self::PSO_UPDATE_KEY);
            return false;
        }

        // Get the deployments
        if (!$this->getDeployments()) {
            Redis::del(self::PSO_UPDATE_KEY);
            return false;
        }

        // Get the jobs
        if (!$this->getJobs()) {
            Redis::del(self::PSO_UPDATE_KEY);
            return false;
        }

        // Get the VolumeSnapshotClasses
        $this->getVolumeSnapshotClasses();

        // Get the VolumeSnapshots
        $this->getVolumeSnapshots();

        // Get FlashArray™ and FlashBlade®
        if (!$this->getArrayInfo()) {
            Redis::del(self::PSO_UPDATE_KEY);
            return false;
        }

        // Get Pure Storage® array information
        $this->addArrayVolumeInfo();

        // Get Pure Storage® array information
        $this->addImportedVolumeInfo();

        // Check for released PV's
        Redis::set(self::VALID_PSO_DATA_KEY, time());
        Redis::expire(self::VALID_PSO_DATA_KEY, $this->refreshTimeout);

        $this->errorSource = '';
        $this->errorMessage = '';
        Redis::del(self::PSO_UPDATE_KEY, time());
        Log::notice('     Refresh data completed.');
        return true;
    }

    public function dashboard()
    {
        $this->RefreshData();

        $dashboard = null;

        $dashboard['volumeCount'] = count(PsoPersistentVolumeClaim::items(PsoPersistentVolumeClaim::PREFIX, 'uid'));
        $dashboard['orphanedCount'] = count(
            PsoPersistentVolume::items(
                PsoPersistentVolume::PREFIX,
                'isOrphaned'
            )
        );
        $dashboard['releasedCount'] = count(
            PsoPersistentVolume::items(
                PsoPersistentVolume::PREFIX,
                'isReleased'
            )
        );
        $dashboard['storageclassCount'] = count(PsoStorageClass::items(PsoStorageClass::PREFIX, 'name'));
        $dashboard['snapshotclassCount'] = count(
            PsoVolumeSnapshotClass::items(PsoVolumeSnapshotClass::PREFIX, 'name')
        );
        $dashboard['snapshotCount'] = count(PsoVolumeSnapshot::items(PsoVolumeSnapshot::PREFIX, 'uid'));
        $dashboard['orphanedSnapshotCount'] = count(PsoVolumeSnapshot::items(PsoVolumeSnapshot::PREFIX, 'orphaned'));
        $dashboard['arrayCount'] = count(PsoArray::items(PsoArray::PREFIX, 'name'));
        $dashboard['offlineArrayCount'] = count(PsoArray::items(PsoArray::PREFIX, 'offline'));

        $vols = [];
        foreach (PsoPersistentVolumeClaim::items(PsoPersistentVolumeClaim::PREFIX, 'uid') as $uid) {
            $pvc = new PsoPersistentVolumeClaim($uid);
            $pv = new PsoPersistentVolume($pvc->volumeName);

            if (($pv->isOrphaned == null) and ($pv->isReleased == null) and ($pv->pureArrayType == 'FA')) {
                $vol['uid'] = $uid;
                $vol['name'] = $pvc->name;
                $vol['namespace'] = $pvc->namespace;
                $vol['pureName'] = $pv->pureName;
                $vol['size'] = $pv->pureSize;
                $vol['sizeFormatted'] = $pv->pureSizeFormatted;
                $vol['used'] = $pv->pureUsed;
                $vol['usedFormatted'] = $pv->pureUsedFormatted;
                $vol['growth'] = $pv->pureTotal - $pv->pure24hHistoricTotal;
                $vol['growthFormatted'] = $this->formatBytes(
                    $pv->pureTotal -
                    $pv->pure24hHistoricTotal,
                    2
                );
                $vol['status'] = $pv->status_phase;

                if ($pv->pureSize !== null) {
                    $vol['growthPercentage'] = ($pv->pureTotal - $pv->pure24hHistoricTotal) * $pv->pureDrr /
                        $pv->pureSize * 100;
                } else {
                    $vol['growthPercentage'] = 0;
                }
                array_push($vols, $vol);
            }
        }

        $uids = array_column($vols, 'uid');
        $growths = array_column($vols, 'growthPercentage');

        array_multisort($growths, SORT_DESC, $uids, SORT_DESC, $vols);

        $dashboard['top10GrowthVols'] = array_slice($vols, 0, 10);

        return $dashboard;
    }

    public function volumes()
    {
        $this->RefreshData();

        $volumes = [];
        foreach (PsoPersistentVolumeClaim::items(PsoPersistentVolumeClaim::PREFIX, 'uid') as $uid) {
            $volume = new PsoPersistentVolumeClaim($uid);
            array_push($volumes, $volume->asArray());
        }
        return $volumes;
    }

    public function orphaned()
    {
        $this->RefreshData();

        $volumes = [];
        foreach (PsoPersistentVolume::items(PsoPersistentVolume::PREFIX, 'isOrphaned') as $name) {
            $volume = new PsoPersistentVolume($name);
            array_push($volumes, $volume->asArray());
        }
        foreach (PsoPersistentVolume::items(PsoPersistentVolume::PREFIX, 'isReleased') as $name) {
            $volume = new PsoPersistentVolume($name);
            array_push($volumes, $volume->asArray());
        }
        return $volumes;
    }

    public function arrays()
    {
        $this->RefreshData();

        $psoArrays = [];

        foreach (PsoArray::items(PsoArray::PREFIX, 'mgmtEndPoint') as $item) {
            $myarray = new PsoArray($item);

            if (($myarray->size == null) or ($myarray->used == null) or ($myarray->volumeCount == null)) {
                $size = 0;
                $used = 0;
                $volumeCount = 0;
                $storageClasses = [];

                foreach (PsoPersistentVolumeClaim::items(PsoPersistentVolumeClaim::PREFIX, 'uid') as $uid) {
                    $myPvc = new PsoPersistentVolumeClaim($uid);
                    $myPv = new PsoPersistentVolume($myPvc->volumeName);

                    if ($myPv->pureArrayMgmtEndPoint == $item) {
                        $size = $size + $myPv->pureSize;
                        $used = $used + $myPv->pureUsed;
                        $volumeCount = $volumeCount + 1;
                        if (
                            !in_array($myPvc->storageClassName, $storageClasses) and
                            ($myPvc->storageClassName !== null)
                        ) {
                            array_push($storageClasses, $myPvc->storageClassName);
                        }
                    }
                }

                $myarray->size = $size;
                $myarray->sizeFormatted = $this->formatBytes($size, 2);
                $myarray->used = $used;
                $myarray->usedFormatted = $this->formatBytes($used, 2);
                $myarray->volumeCount = $volumeCount;
                $myarray->storageClasses = $storageClasses;
            }
            array_push($psoArrays, $myarray->asArray());
        }

        return $psoArrays;
    }

    public function namespaces()
    {
        $this->RefreshData();

        $namespaces = [];
        $pureStorageClasses = PsoStorageClass::items(PsoStorageClass::PREFIX, 'name');

        foreach (PsoPersistentVolumeClaim::items(PsoPersistentVolumeClaim::PREFIX, 'namespace') as $item) {
            $pureSize = 0;
            $pureUsed = 0;
            $pureVolumes = 0;
            $storageclasses = [];

            foreach (PsoPersistentVolumeClaim::items(PsoPersistentVolumeClaim::PREFIX, 'uid') as $uid) {
                $myPvc = new PsoPersistentVolumeClaim($uid);
                $myPv = new PsoPersistentVolume($myPvc->volumeName);

                if (($myPvc->namespace == $item) and in_array($myPvc->storageClassName, $pureStorageClasses)) {
                    $pureSize = $pureSize + $myPv->pureSize;
                    $pureUsed = $pureUsed + $myPv->pureUsed;
                    $pureVolumes = $pureVolumes + 1;
                    if (!in_array($myPvc->storageClassName, $storageclasses)) {
                        array_push($storageclasses, $myPvc->storageClassName);
                    }
                }
            }

            $namespaceInfo = new PsoNamespace($item);
            $namespaceInfo->size = $pureSize;
            $namespaceInfo->sizeFormatted = $this->formatBytes($pureSize, 2);
            $namespaceInfo->used = $pureUsed;
            $namespaceInfo->usedFormatted = $this->formatBytes($pureUsed, 2);
            $namespaceInfo->volumeCount = $pureVolumes;
            $namespaceInfo->storageClasses = implode(', ', $storageclasses);

            array_push($namespaces, $namespaceInfo->asArray());
        }
        return $namespaces;
    }

    public function storageclasses()
    {
        $this->RefreshData();

        $storageclasses = [];

        foreach (PsoStorageClass::items(PsoStorageClass::PREFIX, 'name') as $storageclass) {
            $pureSize = 0;
            $pureUsed = 0;
            $pureVolumes = 0;

            foreach (PsoPersistentVolumeClaim::items(PsoPersistentVolumeClaim::PREFIX, 'uid') as $uid) {
                $myPvc = new PsoPersistentVolumeClaim($uid);
                $myPv = new PsoPersistentVolume($myPvc->volumeName);

                if ($myPvc->storageClassName == $storageclass) {
                    $pureSize = $pureSize + $myPv->pureSize;
                    $pureUsed = $pureUsed + $myPv->pureUsed;
                    $pureVolumes = $pureVolumes + 1;
                }
            }

            $storageclassInfo = new PsoStorageClass($storageclass);
            $storageclassInfo->size = $pureSize;
            $storageclassInfo->sizeFormatted = $this->formatBytes($pureSize, 2);
            $storageclassInfo->used = $pureUsed;
            $storageclassInfo->usedFormatted = $this->formatBytes($pureUsed, 2);
            $storageclassInfo->volumeCount = $pureVolumes;

            array_push($storageclasses, $storageclassInfo->asArray());
        }
        return $storageclasses;
    }

    public function volumesnapshotclasses()
    {
        $this->RefreshData();

        $volumesnapshotclasses = [];

        foreach (PsoVolumeSnapshotClass::items(PsoVolumeSnapshotClass::PREFIX, 'name') as $volumesnapshotclass) {
            $pureSize = 0;
            $pureUsed = 0;
            $pureVolumes = 0;

            foreach (PsoVolumeSnapshot::items(PsoVolumeSnapshot::PREFIX, 'uid') as $uid) {
                $mysnap = new PsoVolumeSnapshot($uid);

                if ($mysnap->snapshotClassName == $volumesnapshotclass) {
                    $pureSize = $pureSize + $mysnap->pureSize;
                    $pureUsed = $pureUsed + $mysnap->pureUsed;
                    $pureVolumes = $pureVolumes + 1;
                }
            }

            $volumeSnapshotClassInfo = new PsoVolumeSnapshotClass($volumesnapshotclass);
            $volumeSnapshotClassInfo->size = $pureSize;
            $volumeSnapshotClassInfo->sizeFormatted = $this->formatBytes($pureSize, 2);
            $volumeSnapshotClassInfo->used = $pureUsed;
            $volumeSnapshotClassInfo->usedFormatted = $this->formatBytes($pureUsed, 2);
            $volumeSnapshotClassInfo->volumeCount = $pureVolumes;

            array_push($volumesnapshotclasses, $volumeSnapshotClassInfo->asArray());
        }
        return $volumesnapshotclasses;
    }

    public function volumesnapshots()
    {
        $this->RefreshData();

        $volumesnapshots = [];
        foreach (PsoVolumeSnapshot::items(PsoVolumeSnapshot::PREFIX, 'uid') as $uid) {
            $snapshot = new PsoVolumeSnapshot($uid);
            array_push($volumesnapshots, $snapshot->asArray());
        }
        return $volumesnapshots;
    }

    public function orphanedsnapshots()
    {
        $this->RefreshData();

        $orphanedsnapshots = [];
        foreach (PsoVolumeSnapshot::items(PsoVolumeSnapshot::PREFIX, 'orphaned') as $uid) {
            $snapshot = new PsoVolumeSnapshot($uid);
            array_push($orphanedsnapshots, $snapshot->asArray());
        }
        return $orphanedsnapshots;
    }

    public function labels()
    {
        $this->RefreshData();

        $labels = [];
        $pureStorageClasses = PsoStorageClass::items(PsoStorageClass::PREFIX, 'name');

        foreach (PsoPersistentVolumeClaim::items(PsoPersistentVolumeClaim::PREFIX, 'labels') as $label) {
            $pureSize = 0;
            $pureUsed = 0;
            $pureVolumes = 0;
            $storageclasses = [];

            foreach (PsoPersistentVolumeClaim::items(PsoPersistentVolumeClaim::PREFIX, 'uid') as $uid) {
                $myPvc = new PsoPersistentVolumeClaim($uid);
                $myPv = new PsoPersistentVolume($myPvc->volumeName);

                if (is_array($myPvc->labels)) {
                    if (in_array($label, $myPvc->labels) and in_array($myPvc->storageClassName, $pureStorageClasses)) {
                        $pureSize = $pureSize + $myPv->pureSize;
                        $pureUsed = $pureUsed + $myPv->pureUsed;
                        $pureVolumes = $pureVolumes + 1;
                        if (!in_array($myPvc->storageClassName, $storageclasses)) {
                            array_push($storageclasses, $myPvc->storageClassName);
                        }
                    }
                }
            }

            if ($pureVolumes > 0) {
                $labelInfo = new PsoLabels($label);
                if ($label !== '') {
                    $labelInfo->label = $label;
                    $labelInfo->key = explode('=', $label)[0];
                    $labelInfo->value = explode('=', $label)[1];
                } else {
                    $labelInfo->label = '';
                    $labelInfo->key = '';
                    $labelInfo->value = '';
                }
                $labelInfo->size = $pureSize;
                $labelInfo->sizeFormatted = $this->formatBytes($pureSize, 2);
                $labelInfo->used = $pureUsed;
                $labelInfo->usedFormatted = $this->formatBytes($pureUsed, 2);
                $labelInfo->volumeCount = $pureVolumes;
                $labelInfo->storageClasses = implode(', ', $storageclasses);

                array_push($labels, $labelInfo->asArray());
            }
        }

        return $labels;
    }

    public function pods()
    {
        $this->RefreshData();

        $pods = [];
        $pvcList = PsoPersistentVolumeClaim::items(PsoPersistentVolumeClaim::PREFIX, 'namespaceName');

        foreach (PsoPod::items(PsoPod::PREFIX, 'uid') as $uid) {
            $pod = new PsoPod($uid);

            $pvcs = [];
            $pvcLinks = [];
            $pureSize = 0;
            $pureUsed = 0;
            $volumeCount = 0;
            $storageClasses = [];
            $storageClasses = [];

            foreach (($pod->pvcNamespaceName ?? []) as $item) {
                if (in_array($item, $pvcList)) {
                    $namespace = explode(':', $item)[0];
                    $name = explode(':', $item)[1];

                    $uid = PsoPersistentVolumeClaim::getUidByNamespaceName($namespace, $name);
                    $myPvc = new PsoPersistentVolumeClaim($uid);
                    $myPv = new PsoPersistentVolume($myPvc->volumeName);

                    array_push($pvcs, $item);
                    $volumeCount = $volumeCount + 1;
                    $pureSize = $pureSize + $myPv->pureSize;
                    $pureUsed = $pureUsed + $myPv->pureUsed;
                    if (!in_array($myPvc->storageClassName, $storageClasses)) {
                        array_push($storageClasses, $myPvc->storageClassName);
                    }

                    array_push(
                        $pvcLinks,
                        '<a href="' . route(
                            'Storage-Volumes',
                            ['volume_keyword' => $myPvc->uid],
                            false
                        ) . '">' . $myPvc->name . '</a>'
                    );
                }
            }

            $pod->size = $pureSize;
            $pod->sizeFormatted = $this->formatBytes($pureSize, 2);
            $pod->used = $pureUsed;
            $pod->usedFormatted = $this->formatBytes($pureUsed, 2);
            $pod->volumeCount = $volumeCount;
            $pod->storageClasses = $storageClasses;
            $pod->pvcLink = $pvcLinks;

            array_push($pods, $pod->asArray());
        }

        return $pods;
    }

    public function jobs()
    {
        $this->RefreshData();

        $jobs = [];

        foreach (PsoJob::items(PsoJob::PREFIX, 'uid') as $uid) {
            $job = new PsoJob($uid);
            $pvcs = [];
            $pvcLinks = [];
            $pureSize = 0;
            $pureUsed = 0;
            $volumeCount = 0;
            $storageClasses = [];
            $storageClasses = [];

            foreach (($job->pvcNamespaceName ?? []) as $item) {
                $namespace = explode(':', $item)[0];
                $name = explode(':', $item)[1];

                $uid = PsoPersistentVolumeClaim::getUidByNamespaceName($namespace, $name);
                $myPvc = new PsoPersistentVolumeClaim($uid);
                $myPv = new PsoPersistentVolume($myPvc->volumeName);

                array_push($pvcs, $item);
                $volumeCount = $volumeCount + 1;
                $pureSize = $pureSize + $myPv->pureSize;
                $pureUsed = $pureUsed + $myPv->pureUsed;
                if (!in_array($myPvc->storageClassName, $storageClasses)) {
                    array_push($storageClasses, $myPvc->storageClassName);
                }

                array_push(
                    $pvcLinks,
                    '<a href="' . route(
                        'Storage-Volumes',
                        ['volume_keyword' => $myPvc->uid],
                        false
                    ) . '">' . $myPvc->name . '</a>'
                );
            }

            $job->size = $pureSize;
            $job->sizeFormatted = $this->formatBytes($pureSize, 2);
            $job->used = $pureUsed;
            $job->usedFormatted = $this->formatBytes($pureUsed, 2);
            $job->volumeCount = $volumeCount;
            $job->storageClasses = $storageClasses;
            $job->pvcLink = $pvcLinks;

            array_push($jobs, $job->asArray());
        }

        return $jobs;
    }

    public function nodes()
    {
        $nodes = [];
        foreach (PsoNode::items(PsoNode::PREFIX, 'uid') as $uid) {
            $node = new PsoNode($uid);

            array_push($nodes, $node->asArray());
        }

        return $nodes;
    }

    public function deployments()
    {
        $this->RefreshData();

        $deployments = [];

        foreach (PsoDeployment::items(PsoDeployment::PREFIX, 'uid') as $deploymentUid) {
            $deployment = new PsoDeployment($deploymentUid);

            $pureSize = 0;
            $pureUsed = 0;
            $pureVolumes = 0;
            $storageclasses = [];

            foreach (PsoPersistentVolumeClaim::items(PsoPersistentVolumeClaim::PREFIX, 'uid') as $uid) {
                $myPvc = new PsoPersistentVolumeClaim($uid);
                $myPv = new PsoPersistentVolume($myPvc->volumeName);

                if ((in_array($myPvc->namespace . ':' . $myPvc->name, $deployment->namespaceNames))) {
                    $pureSize = $pureSize + $myPv->pureSize;
                    $pureUsed = $pureUsed + $myPv->pureUsed;
                    $pureVolumes = $pureVolumes + 1;
                    if (!in_array($myPvc->storageClassName, $storageclasses)) {
                        array_push($storageclasses, $myPvc->storageClassName);
                    }
                }
            }

            $deployment->size = $pureSize;
            $deployment->sizeFormatted = $this->formatBytes($pureSize, 2);
            $deployment->used = $pureUsed;
            $deployment->usedFormatted = $this->formatBytes($pureUsed, 2);
            $deployment->volumeCount = $pureVolumes;
            $deployment->storageClasses = implode(', ', $storageclasses);

            array_push($deployments, $deployment->asArray());
        }

        return $deployments;
    }

    public function statefulsets()
    {
        $this->RefreshData();

        $statefulsets = [];
        foreach (PsoStatefulSet::items(PsoStatefulSet::PREFIX, 'uid') as $statefulsetUid) {
            $myset = new PsoStatefulSet($statefulsetUid);

            $pureSize = 0;
            $pureUsed = 0;
            $pureVolumes = 0;
            $storageclasses = [];

            foreach (PsoPersistentVolumeClaim::items(PsoPersistentVolumeClaim::PREFIX, 'uid') as $uid) {
                $myPvc = new PsoPersistentVolumeClaim($uid);
                $myPv = new PsoPersistentVolume($myPvc->volumeName);

                if ((in_array($myPvc->namespace . ':' . $myPvc->name, $myset->namespaceNames))) {
                    $pureSize = $pureSize + $myPv->pureSize;
                    $pureUsed = $pureUsed + $myPv->pureUsed;
                    $pureVolumes = $pureVolumes + 1;
                    if (!in_array($myPvc->storageClassName, $storageclasses)) {
                        array_push($storageclasses, $myPvc->storageClassName);
                    }
                }
            }

            $myset->size = $pureSize;
            $myset->sizeFormatted = $this->formatBytes($pureSize, 2);
            $myset->used = $pureUsed;
            $myset->usedFormatted = $this->formatBytes($pureUsed, 2);
            $myset->volumeCount = $pureVolumes;
            $myset->storageClasses = implode(', ', $storageclasses);

            array_push($statefulsets, $myset->asArray());
        }

        return $statefulsets;
    }

    public function portalInfo()
    {
        $portalInfo['totalUsed'] = $this->formatBytes($this->psoInfo->totalUsed);
        $portalInfo['totalSize'] = $this->formatBytes($this->psoInfo->totalSize);
        $portalInfo['totalUsedRaw'] = $this->psoInfo->totalUsed;
        $portalInfo['totalSizeRaw'] = $this->psoInfo->totalSize;
        $portalInfo['totalOrphanedRaw'] = $this->psoInfo->totalOrphanedUsed;
        $portalInfo['totalSnapshotRaw'] = $this->psoInfo->totalSnapshotUsed;
        $portalInfo['lastRefesh'] = Redis::get(self::VALID_PSO_DATA_KEY);

        $portalInfo['totalIopsRead'] = $this->psoInfo->totalIopsRead;
        $portalInfo['totalIopsWrite'] = $this->psoInfo->totalIopsWrite;
        $portalInfo['totalBw'] = $this->formatBytes(
            $this->psoInfo->totalBwRead + $this->psoInfo->totalBwWrite,
            1,
            2
        );
        $portalInfo['totalBwRead'] = $this->formatBytes($this->psoInfo->totalBwRead, 1, 2);
        $portalInfo['totalBwWrite'] = $this->formatBytes($this->psoInfo->totalBwWrite, 1, 2);

        $portalInfo['lowMsecRead'] = round($this->psoInfo->lowMsecRead, 2);
        $portalInfo['lowMsecWrite'] = round($this->psoInfo->lowMsecWrite, 2);
        $portalInfo['highMsecRead'] = round($this->psoInfo->highMsecRead, 2);
        $portalInfo['highMsecWrite'] = round($this->psoInfo->highMsecWrite, 2);

        return $portalInfo;
    }

    public function settings()
    {
        $this->RefreshData();
        $settings = $this->psoInfo->asArray();

        // Add PSO CockroachDB volumes to settings
        $backendvols = PsoBackendVolume::items(PsoBackendVolume::PREFIX, 'pureArrayNameVolName');
        if (count($backendvols) > 0) {
            $settings['dbvols'] = [];
            foreach ($backendvols as $pureArrayNameVolName) {
                $backendvol = new PsoBackendVolume($pureArrayNameVolName);

                array_push($settings['dbvols'], $backendvol->asArray());
            }
        }
        return $settings;
    }

    public function log()
    {
        Client::configure($this->master, $this->authentication, ['timeout' => 10]);
        $podLog = new PodLog();

        try {
            $log = $podLog->readLog(
                $this->psoInfo->namespace,
                $this->psoInfo->provisionerPod,
                ['container' => $this->psoInfo->provisionerContainer,
                    'tailLines' => '1000']
            );
            if (isset($log->code)) {
                $log = 'Unable to get access log';
            }
        } catch (Exception $e) {
            unset($e);
            $log = 'Unable to get access log';
        }
        return $log;
    }
}
