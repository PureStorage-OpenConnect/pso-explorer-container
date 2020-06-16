<?php

namespace App;

use App\Api\FlashArrayAPI;
use App\Api\FlashBladeAPI;
use App\Http\Classes\PsoArray;
use App\Http\Classes\PsoDeployment;
use App\Http\Classes\PsoInformation;
use App\Http\Classes\PsoLabels;
use App\Http\Classes\PsoNamespace;
use App\Http\Classes\PsoPersistentVolumeClaim;
use App\Http\Classes\PsoStatefulSet;
use App\Http\Classes\PsoStorageClass;
use Exception;
use Illuminate\Support\Facades\Redis;
use Kubernetes\API\Deployment;
use Kubernetes\API\PersistentVolume;
use Kubernetes\API\PersistentVolumeClaim;
use Kubernetes\API\Pod;
use Kubernetes\API\Secret;
use Kubernetes\API\StatefulSet;
use Kubernetes\API\StorageClass;
use KubernetesRuntime\Client;
use Kubernetes\Model\Io\K8s\Api\Apps\V1\StatefulSetList;
use Kubernetes\Model\Io\K8s\Api\Apps\V1\DeploymentList;
use Kubernetes\Model\Io\K8s\Api\Core\V1\Container;
use Kubernetes\Model\Io\K8s\Api\Core\V1\EnvVar;
use Kubernetes\Model\Io\K8s\Api\Core\V1\PersistentVolumeList;
use Kubernetes\Model\Io\K8s\Api\Core\V1\Volume;
use Kubernetes\Model\Io\K8s\Api\Storage\V1\StorageClassList;


class pso
{
    public const VALID_PSO_DATA_KEY = 'pso:timestamp';
    public const PURE_PROVISIONERS = ['pure-provisioner', 'pure-csi'];

    private $master = null;
    private $authentication = null;
    private $refresh_timeout = 900;

    public $pso_info = null;
    public $pso_found = false;
    public $error_source = '';
    public $error_message = '';
    public $values_yaml = '';

    /**
     * Convert float in bytes to formatted string
     *
     * @return string
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('Bi', 'Ki', 'Mi', 'Gi', 'Ti', 'Pi', 'Ei');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
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
        if ($object == null) return $array;

        foreach ($object as $key => $value) {
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

    /**
     * Connect to Kubernetes to find PSO namespace and prefix
     *
     * @return boolean
     */
    private function getPsoDetails()
    {
        // Initialize variables
        $this->pso_found = false;

        // Try to connect to Kubernetes cluster API, catch any curl errors
        // Using a custom timeout for the CURL request, so we don't timeout our session
        try {
            Client::configure($this->master, $this->authentication, ['timeout' => 10]);
            $pod = new Pod();
            $pod_list = $pod->list('');
        } catch (Exception $e) {
            // If we catch a CURL error, return an error message
            $this->error_source = 'k8s';
            $this->error_message = $e->getMessage();
            unset($e);
            return false;
        }

        // If the CURL connection was successful, check if the response was also successful
        // This could be an authentication error for example
        if (isset($pod_list->status))   {
            if ($pod_list->status == 'Failure') {
                // If status is set to Failure, we hit an error, so we return an error message
                $this->error_source = 'k8s';
                $this->error_message = $pod_list->message;
                return false;
            }
        }

        // Loop through the POD's to find PSO namespace and prefix
        foreach ($pod_list->items as $item) {
            foreach ($item->spec->containers as $containers) {
                if (isset($containers->env)) {
                    foreach ($containers->env as $env) {
                        if ($env->name == 'PURE_K8S_NAMESPACE') {
                            // If PSO is found, set pso_found to true and store prefix and namespace in Redis
                            $this->pso_found = true;
                            $this->pso_info->prefix = $env->value;
                            $this->pso_info->namespace = $item->metadata->namespace;
                            break;
                        }
                    if ($this->pso_found) break;
                    }
                }
                if ($this->pso_found) break;
            }
            if ($this->pso_found) break;
        }
        if (!$this->pso_found) {
            // If PSO was not found, return an error
            $this->error_source = 'pso';
            $this->error_message = 'Unable to find PSO namespace';
            return false;
        } else {
            return true;
        }
    }

    /**
     * Connect to the Pure Storage arrays (FlashArray and FlashBlade) to retrieve
     * management IP's and API tokens
     *
     * @return boolean
     */
    private function getArrayInfo()
    {
        // Get the PSO secret from Kubernetes to retrieve the MgmtEndPoint and APIToken
        Client::configure($this->master, $this->authentication);
        $secret = new Secret($this->pso_info->namespace);
        $pso_secret_data = $secret->read($this->pso_info->namespace, 'pure-provisioner-secret')->data;
        $pso_config = json_decode(base64_decode($pso_secret_data['pure.json'], true));

        $pso_yaml = $this->objectToArray($pso_config);
        if ($pso_yaml !== []) $this->pso_info->yaml = yaml_emit(["arrays" => $pso_yaml[0]]);

        // Get FlashArrays information
        if (isset($pso_config->FlashArrays)) {
            foreach ($pso_config->FlashArrays as $flasharray) {
                // TO DO: Could we possibly add detection of errors in values.yaml / pure.json

                $newArray = new PsoArray($flasharray->MgmtEndPoint);
                $newArray->apiToken = $flasharray->APIToken;
                if (isset($flasharray->Labels)) {
                    $myLabels = [];
                    foreach ($flasharray->Labels as $key => $value) {
                        array_push($myLabels, $key . '=' . $value);
                    }
                    $newArray->labels = $myLabels;
                }

                $fa_api = new FlashArrayAPI;
                try {
                    // Connect to the array for the array name
                    $fa_api->authenticate($flasharray->MgmtEndPoint, $flasharray->APIToken);
                    $array_details = $fa_api->GetArray();
                    $model_details = $fa_api->GetArray('controllers=true');

                    $newArray->name = $array_details['array_name'];
                    $newArray->model = 'Pure Storage FlashArray ' . $model_details[0]['model'];
                } catch (Exception $e) {
                    $newArray->name = $flasharray->MgmtEndPoint;
                    $newArray->model = 'Unknown';
                    $newArray->offline = $flasharray->MgmtEndPoint;
                    unset ($e);
                }
            }
        }

        // Get FlashBlade information
        if (isset($pso_config->FlashBlades)) {
            foreach ($pso_config->FlashBlades as $flashblade) {
                // TO DO: Here we could add detection of errors in values.yaml / pure.json

                $newArray = new PsoArray($flashblade->MgmtEndPoint);
                $newArray->apiToken = $flashblade->APIToken;
                if (isset($flashblade->Labels)) {

                    $myLabels = [];
                    foreach ($flashblade->Labels as $key => $value) {
                        array_push($myLabels, $key . '=' . $value);
                    }
                    $newArray->labels = $myLabels;
                }

                $fb_api = new FlashBladeAPI($flashblade->MgmtEndPoint, $flashblade->APIToken);
                try {
                    // Connect to the array for the array name
                    $fb_api->authenticate();
                    $array = $fb_api->GetArray();

                    $newArray->name = $array['items'][0]['name'];
                    $newArray->model = 'Pure Storage FlashBlade';
                } catch (Exception $e) {
                    $newArray->name = $flashblade->MgmtEndPoint;
                    $newArray->model = 'Offline';
                    $newArray->offline = $flashblade->MgmtEndPoint;
                    unset ($e);
                }
            }
        }

        if (count(PsoArray::items(PsoArray::PREFIX, 'mgmtEndPoint')) == 0) {
            // If no arrays were found, return an error
            $this->pso_found = false;
            $this->error_source = 'pso';
            $this->error_message = 'No arrays configured for PSO! Check the syntax of your values.yaml file.';
            return false;
        };

        return true;
    }

    private function getStorageClasses()
    {
        // Retrieve all Kubernetes StorageClasses for this cluster
        Client::configure($this->master, $this->authentication);
        $storageclass = new StorageClass();
        $storageclass_list = $storageclass->list();

        if (isset($storageclass_list->items)) {
            foreach ($storageclass_list->items as $item) {
                // Add all storageclasses that use PSO
                if (in_array($item->provisioner, self::PURE_PROVISIONERS)) {
                    $mystorageclass = new PsoStorageClass($item->metadata->name);
                }
            }
        }
        return true;
    }

    private function getStatefulsets()
    {
        // Retrieve all Kubernetes StatefulSets for this cluster
        Client::configure($this->master, $this->authentication);
        $statefulset = new StatefulSet();
        $statefulset_list = $statefulset->list('');

        if (isset($statefulset_list->items)) {
            foreach ($statefulset_list->items as $item) {
                if (isset($item->spec->volumeClaimTemplates)) {

                    $vols = [];
                    foreach ($item->spec->volumeClaimTemplates as $template) {
                        for ($i = 0; $i < $item->spec->replicas;$i++) {
                            if (in_array($template->spec->storageClassName, PsoStorageClass::items(PsoStorageClass::PREFIX, 'name')) or $template->spec->storageClassName == null) {
                                array_push($vols, $item->metadata->namespace . ':' . $template->metadata->name . '-' . $item->metadata->name . '-' . $i);
                            }
                        }
                    }

                    if ($vols !== []) {
                        $myset = new PsoStatefulSet($item->metadata->uid);
                        $myset->name = $item->metadata->name;
                        $myset->namespace = $item->metadata->namespace;
                        $myset->namespace_volnames = $vols;
                    }
                }
            }
        }
    }

    private function getPersistentVolumeClaims()
    {
        // Retrieve all Kubernetes PVC's for this cluster
        Client::configure($this->master, $this->authentication);
        $pvc = new PersistentVolumeClaim();
        $pvc_list = $pvc->list('');

        foreach ($pvc_list->items as $item) {
            $myvol = new PsoPersistentVolumeClaim($item->metadata->uid);
            $myvol->name = $item->metadata->name;
            $myvol->namespace = $item->metadata->namespace;
            $myvol->namespace_name = $item->metadata->namespace . ':' . $item->metadata->name;
            $myvol->size = $item->spec->resources->requests['storage'];
            $myvol->storageClass =  $item->spec->storageClassName;
            $myvol->status = $item->status->phase;

            if (isset($item->metadata->labels)) {
                $labels = [];
                foreach (array_keys($item->metadata->labels) as $key) {
                    array_push($labels, $key . '=' . $item->metadata->labels[$key]);
                }
                $myvol->labels = $labels;
            }

            if (isset($item->spec->volumeName)) {
                $myvol->pv_name = $item->spec->volumeName;
            }
        }
    }

    private function getDeployments()
    {
        // Retrieve all Kubernetes StatefulSets for this cluster
        Client::configure($this->master, $this->authentication);
        $deployment = new Deployment();
        $deployment_list = $deployment->list('');

        if (isset($deployment_list->items)) {
            foreach ($deployment_list->items as $item) {
                if (isset($item->spec->template->spec->volumes)) {
                    foreach ($item->spec->template->spec->volumes as $vol) {
                        if (isset($vol->persistentVolumeClaim->claimName)) {
                            $namespace_volname = $item->metadata->namespace . ':' .  $vol->persistentVolumeClaim->claimName;
                            if (in_array($namespace_volname, PsoPersistentVolumeClaim::items(PsoPersistentVolumeClaim::PREFIX, 'namespace_name'))) {
                                $mydeployment = new PsoDeployment($item->metadata->uid);
                                $mydeployment->name = $item->metadata->name;
                                $mydeployment->namespace = $item->metadata->namespace;
                                $mydeployment->volumeCount = $mydeployment->volumeCount + 1;
                                $mydeployment->namespace_volnames = [$namespace_volname];
                            }
                        }
                    }
                }
            }
        }
    }

    private function addArrayVolumeInfo()
    {
        $total_size = 0;
        $total_used = 0;

        $total_iops_read = 0;
        $total_iops_write = 0;
        $total_bw_read = 0;
        $total_bw_write = 0;
        $low_msec_read = -1;
        $low_msec_write = -1;
        $high_msec_read = 0;
        $high_msec_write = 0;

        $perf_count = 0;

        foreach (PsoArray::items(PsoArray::PREFIX, 'mgmtEndPoint') as $item) {
            $array = new PsoArray($item);

            if (strpos($array->model, 'FlashArray') and ($array->offline == null)) {
                $fa_api = new FlashArrayAPI;
                $fa_api->authenticate($array->mgmtEndPoint, $array->apiToken);

                $vols = $fa_api->GetVolumes([
                    'names' => $this->pso_info->prefix . '-pvc-*',
                    'space' => 'true',
                ]);

                if (isset($vols)) {
                    foreach ($vols as $vol) {
                        if ($this->startsWith($this->pso_info->prefix . '-pvc-', $vol['name'])) {
                            $uid = str_ireplace($this->pso_info->prefix . '-pvc-', '', $vol['name']) ;

                            $myvol = new PsoPersistentVolumeClaim($uid);
                            $myvol->pure_name = $vol['name'] ?? '';
                            $myvol->pure_size = $vol['size'] ?? 0;
                            $myvol->pure_sizeFormatted = $this->formatBytes($myvol->pure_size, 2);
                            $myvol->pure_used = $vol['size'] * (1 - $vol['thin_provisioning'] ?? 0);
                            $myvol->pure_usedFormatted = $this->formatBytes($myvol->pure_used, 2);
                            $myvol->pure_drr = $vol['data_reduction'] ?? 1;
                            $myvol->pure_thinProvisioning = $vol['thin_provisioning'] ?? 0;
                            $myvol->pure_arrayName = $array->name;
                            $myvol->pure_arrayMgmtEndPoint = $array->mgmtEndPoint;
                            $myvol->pure_snapshots = $vol['snapshots'] ?? 0;
                            $myvol->pure_volumes = $vol['volumes'] ?? 0;
                            $myvol->pure_sharedSpace = $vol['shared_space'] ?? 0;
                            $myvol->pure_totalReduction = $vol['total_reduction'] ?? 1;
                            if ($myvol->name == null) {
                                $myvol->pure_orphaned = $uid;
                            }

                            $total_used = $total_used + $vol['size']  * (1 - $vol['thin_provisioning'] ?? 0);
                            $total_size = $total_size + $vol['size'] ?? 0;
                        }
                    }
                }

                $vols_perf = $fa_api->GetVolumes([
                    'names' => $this->pso_info->prefix . '-pvc-*',
                    'action' => 'monitor',
                ]);

                if (isset($vols_perf)) {
                    foreach ($vols_perf as $vol_perf) {
                        if ($this->startsWith($this->pso_info->prefix . '-pvc-', $vol_perf['name'])) {
                            $uid = str_ireplace($this->pso_info->prefix . '-pvc-', '', $vol_perf['name']) ;

                            $myvol = new PsoPersistentVolumeClaim($uid);
                            $myvol->pure_reads_per_sec = $vol_perf['reads_per_sec'];
                            $myvol->pure_writes_per_sec = $vol_perf['writes_per_sec'];
                            $myvol->pure_input_per_sec = $vol_perf['input_per_sec'];
                            $myvol->pure_input_per_sec_formatted = $this->formatBytes($vol_perf['input_per_sec'], 1);
                            $myvol->pure_output_per_sec = $vol_perf['output_per_sec'];
                            $myvol->pure_output_per_sec_formatted = $this->formatBytes($vol_perf['output_per_sec'], 1);
                            $myvol->pure_usec_per_read_op = round($vol_perf['usec_per_read_op'] / 1000, 2);
                            $myvol->pure_usec_per_write_op = round($vol_perf['usec_per_write_op'] / 1000, 2);

                            $total_iops_read = $total_iops_read + $vol_perf['reads_per_sec'];
                            $total_iops_write = $total_iops_write + $vol_perf['writes_per_sec'];
                            $total_bw_read = $total_bw_read + $vol_perf['output_per_sec'];
                            $total_bw_write = $total_bw_write + $vol_perf['input_per_sec'];

                            if (($vol_perf['usec_per_read_op'] / 1000 < $low_msec_read) or ($low_msec_read = -1)) $low_msec_read = $vol_perf['usec_per_read_op'] / 1000;
                            if (($vol_perf['usec_per_write_op'] / 1000 < $low_msec_write) or ($low_msec_write = -1)) $low_msec_write = $vol_perf['usec_per_write_op'] / 1000;
                            if ($vol_perf['usec_per_read_op'] / 1000 > $high_msec_read) $high_msec_read = $vol_perf['usec_per_read_op'] / 1000;
                            if ($vol_perf['usec_per_write_op'] / 1000 > $high_msec_write) $high_msec_write = $vol_perf['usec_per_write_op'] / 1000;

                            $perf_count = $perf_count + 1;
                        }
                    }
                }


                $vols_perf = $fa_api->GetVolumes([
                    'names' => $this->pso_info->prefix . '-pvc-*',
                    'space' => 'true',
                    'historical' => '24h',
                ]);

                $vol_hist_size = [];
                if (isset($vols_perf)) {
                    foreach ($vols_perf as $vol_perf) {
                        if ($this->startsWith($this->pso_info->prefix . '-pvc-', $vol_perf['name'])) {
                            $uid = str_ireplace($this->pso_info->prefix . '-pvc-', '', $vol_perf['name']) ;

                            if (isset($vol_hist_size[$uid]['first_date']) and (strtotime($vol_perf['time']) < $vol_hist_size[$uid]['first_date'])) {
                                $vol_hist_size[$uid]['first_used'] = $vol_perf['volumes'] + $vol_perf['snapshots'] + $vol_perf['shared_space'] + $vol_perf['system'];
                                $vol_hist_size[$uid]['first_date'] = strtotime($vol_perf['time']);
                            } elseif (!isset($vol_hist_size[$uid]['first_date'])) {
                                $vol_hist_size[$uid]['first_used'] = $vol_perf['volumes'] + $vol_perf['snapshots'] + $vol_perf['shared_space'] + $vol_perf['system'];
                                $vol_hist_size[$uid]['first_date'] = strtotime($vol_perf['time']);
                            }
                        }
                    }
                }

                foreach (PsoPersistentVolumeClaim::items(PsoPersistentVolumeClaim::PREFIX, 'uid') as $uid) {
                    $vol = new PsoPersistentVolumeClaim($uid);
                    if (isset($vol_hist_size[$uid]['first_used'])) {
                        $vol->pure_24h_historic_used = $vol_hist_size[$uid]['first_used'];
                    }
                }
            } elseif (strpos($array->model, 'FlashBlade') and ($array->offline == null)) {
                $fb_api = new FlashBladeAPI($array->mgmtEndPoint, $array->apiToken);

                try {
                    $fb_api->authenticate();

                    $filesystems = $fb_api->GetFileSystems([
                        'names' => $this->pso_info->prefix . '-pvc-*',
                        'space' => 'true',
                    ]);
                } catch (Exception $e) {
                    unset($e);
                    $filesystems = null;
                }

                if (isset($filesystems['items'])) {
                    foreach ($filesystems['items'] as $filesystem) {
                        if ($this->startsWith($this->pso_info->prefix . '-pvc-', $filesystem['name'])) {
                            $uid = str_ireplace($this->pso_info->prefix . '-pvc-', '', $filesystem['name']) ;

                            $myvol = new PsoPersistentVolumeClaim($uid);
                            $myvol->pure_name = $filesystem['name'] ?? '';
                            $myvol->pure_size = $filesystem['provisioned'] ?? 0;
                            $myvol->pure_sizeFormatted = $this->formatBytes($myvol->pure_size, 2);
                            $myvol->pure_used = $filesystem['space']['virtual'] ?? 0;
                            $myvol->pure_usedFormatted = $this->formatBytes($myvol->pure_used, 2);
                            $myvol->pure_drr = $filesystem['space']['data_reduction'] ?? 1;
                            $myvol->pure_thinProvisioning = 0;
                            $myvol->pure_arrayName = $array->name;
                            $myvol->pure_arrayMgmtEndPoint = $array->mgmtEndPoint;
                            $myvol->pure_snapshots = $filesystem['space']['snapshots'] ?? 0;
                            $myvol->pure_volumes = $filesystem['space']['unique'] ?? 0;
                            $myvol->pure_sharedSpace = 0;
                            $myvol->pure_totalReduction = $filesystem['space']['data_reduction'] ?? 1;
                            if ($myvol->name == null) {
                                $myvol->pure_orphaned = $uid;
                            }

                            $total_used = $total_used + $filesystem['space']['virtual'];
                            $total_size = $total_size + $filesystem['provisioned'];
                        }
                    }
                }
            }
        }
        $this->pso_info->totalsize = $total_size;
        $this->pso_info->totalused = $total_used;

        $this->pso_info->total_iops_read = $total_iops_read;
        $this->pso_info->total_iops_write = $total_iops_write;
        $this->pso_info->total_bw_read = $total_bw_read;
        $this->pso_info->total_bw_write = $total_bw_write;

        if ($low_msec_read = -1) {
            $this->pso_info->low_msec_read = 0;
        } else {
            $this->pso_info->low_msec_read = $low_msec_read;
        }

        if ($low_msec_write = -1) {
            $this->pso_info->low_msec_write = 0;
        } else {
            $this->pso_info->low_msec_write = $low_msec_write;
        }

        $this->pso_info->high_msec_read = $high_msec_read;
        $this->pso_info->high_msec_write = $high_msec_write;
    }

    public function RefreshData($force = false)
    {
        // Only refresh data if the redis data is stale
        if ((Redis::get(self::VALID_PSO_DATA_KEY) !== null) and (!$force)) {
            $this->pso_found = true;
            $this->error_source = '';
            $this->error_message = '';
            return true;
        }

        // Remove stale PSO data from Redis
        Redis::del(self::VALID_PSO_DATA_KEY);
        PsoInformation::deleteAll(PsoInformation::PREFIX);
        PsoArray::deleteAll(PsoArray::PREFIX);
        PsoStorageClass::deleteAll(PsoStorageClass::PREFIX);
        PsoStatefulSet::DeleteAll(PsoStatefulSet::PREFIX);
        PsoPersistentVolumeClaim::deleteAll(PsoPersistentVolumeClaim::PREFIX);
        PsoLabels::deleteAll(PsoLabels::PREFIX);
        PsoNamespace::deleteAll(PsoNamespace::PREFIX);

        // Get PSO namespace and prefix from Kubernetes
        if (!$this->getPsoDetails()) return false;

        // Get FlashArrays and FlashBlades
        if (!$this->getArrayInfo()) return false;

        // Get the storageclasses that use PSO
        if (!$this->getStorageClasses()) return false;

        // Get the statefulsets
        $this->getStatefulsets();

        // Get the persistent volume claims
        $this->getPersistentVolumeClaims();

        // Get the deployments
        $this->getDeployments();

        $this->addArrayVolumeInfo();

        Redis::set(self::VALID_PSO_DATA_KEY, now()->format('Y-m-d H:i:s'));
        Redis::expire(self::VALID_PSO_DATA_KEY, $this->refresh_timeout);

        $this->error_source = '';
        $this->error_message = '';
        return true;
    }

    public function __construct()
    {
        if (file_exists('/var/run/secrets/kubernetes.io')) {
            // Use for in cluster credentials
            $this->master = 'https://kubernetes.default.svc';
            $this->authentication = [
                'caCert' => '/var/run/secrets/kubernetes.io/serviceaccount/ca.crt',
                'token' => '/var/run/secrets/kubernetes.io/serviceaccount/token',
            ];
        } else {
            // Use for developer cluster access Rosmalen
            $this->master = 'https://10.233.0.1';
            $this->authentication = [
                'caCert' => '/Users/rdeenik/LocalFiles/k8s/certs/ca.crt',
                'token' => '/Users/rdeenik/LocalFiles/k8s/certs/token',
            ];

            // Use for test cluster Amsterdam
            $this->master = 'https://10.233.0.1';
            $this->authentication = [
                'caCert' => '/Users/rdeenik/LocalFiles/k8s/certs/ca-amslab.crt',
                'token' => '/Users/rdeenik/LocalFiles/k8s/certs/token-amslab'
            ];
            /*
            // Use for FSA lab
            $this->master = 'https://10.234.0.1';
            $this->authentication = [
                'caCert' => '/Users/rdeenik/LocalFiles/k8s/certs/ca-fsa.crt',
                'token' => '/Users/rdeenik/LocalFiles/k8s/certs/token-fsa'
            ];
            */
        }
        $this->refresh_timeout = env('PSO_REFRESH_TIMEOUT', '900');

        $this->pso_info = new PsoInformation();
        $this->RefreshData();
    }

    public function dashboard()
    {
        $this->RefreshData();

        $dashboard = null;

        $dashboard['volume_count'] = count(PsoPersistentVolumeClaim::items(PsoPersistentVolumeClaim::PREFIX,'uid'));
        $dashboard['orphaned_count'] = count(PsoPersistentVolumeClaim::items(PsoPersistentVolumeClaim::PREFIX,'pure_orphaned'));
        $dashboard['storageclass_count'] = count(PsoStorageClass::items(PsoStorageClass::PREFIX, 'name'));
        $dashboard['statefulset_count'] = count(PsoStatefulSet::items(PsoStatefulSet::PREFIX, 'uid'));
        $dashboard['array_count'] = count(PsoArray::items(PsoArray::PREFIX, 'name'));
        $dashboard['offline_array_count'] = count(PsoArray::items(PsoArray::PREFIX, 'offline'));

        return $dashboard;
    }

    public function volumes()
    {
        $volumes = [];
        foreach (PsoPersistentVolumeClaim::items(PsoPersistentVolumeClaim::PREFIX, 'uid') as $uid)
        {
            $volume = new PsoPersistentVolumeClaim($uid);
            if ($volume->pure_orphaned == null) {
                array_push($volumes, $volume->asArray());
            }
        }
        return $volumes;
    }

    public function orphaned()
    {
        $volumes = [];
        foreach (PsoPersistentVolumeClaim::items(PsoPersistentVolumeClaim::PREFIX, 'pure_orphaned') as $uid)
        {
            $volume = new PsoPersistentVolumeClaim($uid);
            array_push($volumes, $volume->asArray());
        }
        return $volumes;
    }

    public function arrays()
    {
        $pso_arrays = [];

        foreach (PsoArray::items(PsoArray::PREFIX, 'mgmtEndPoint') as $item)
        {
            $myarray = new PsoArray($item);

            if (($myarray->size == null) or ($myarray->used == null) or ($myarray->volumeCount == null)) {
                $size = 0;
                $used = 0;
                $volumeCount = 0;
                $storageClasses = [];

                foreach (PsoPersistentVolumeClaim::items(PsoPersistentVolumeClaim::PREFIX, 'uid') as $uid) {
                    $myvol = new PsoPersistentVolumeClaim($uid);

                    if ($myvol->pure_arrayMgmtEndPoint == $item)
                    {
                        $size = $size + $myvol->pure_size;
                        $used = $used + $myvol->pure_used;
                        $volumeCount = $volumeCount + 1;
                        if (!in_array($myvol->storageClass, $storageClasses)) array_push($storageClasses, $myvol->storageClass);
                    }
                }

                $myarray->size = $size;
                $myarray->sizeFormatted = $this->formatBytes($size, 2);
                $myarray->used = $used;
                $myarray->usedFormatted = $this->formatBytes($used, 2);
                $myarray->volumeCount = $volumeCount;
                $myarray->storageClasses = $storageClasses;
            }
            array_push($pso_arrays, $myarray->asArray());
        }

        return $pso_arrays;
    }

    public function namespaces()
    {
        $namespaces = [];
        $pure_storageclasses = PsoStorageClass::items(PsoStorageClass::PREFIX, 'name');

        foreach (PsoPersistentVolumeClaim::items(PsoPersistentVolumeClaim::PREFIX, 'namespace') as $item)
        {
            $pure_size = 0;
            $pure_used = 0;
            $pure_volumes = 0;
            $storageclasses = [];

            foreach (PsoPersistentVolumeClaim::items(PsoPersistentVolumeClaim::PREFIX, 'uid') as $uid) {
                $myvol = new PsoPersistentVolumeClaim($uid);

                if (($myvol->namespace == $item) and in_array($myvol->storageClass, $pure_storageclasses))
                {
                    $pure_size = $pure_size + $myvol->pure_size;
                    $pure_used = $pure_used + $myvol->pure_used;
                    $pure_volumes = $pure_volumes + 1;
                    if (!in_array($myvol->storageClass, $storageclasses)) array_push($storageclasses, $myvol->storageClass);
                }
            }

            $namespace_info = new PsoNamespace($item);
            $namespace_info->size = $pure_size;
            $namespace_info->sizeFormatted = $this->formatBytes($pure_size, 2);
            $namespace_info->used = $pure_used;
            $namespace_info->usedFormatted = $this->formatBytes($pure_used, 2);
            $namespace_info->volumeCount = $pure_volumes;
            $namespace_info->storageClasses = implode(',', $storageclasses);

            array_push($namespaces, $namespace_info->asArray());
        }
        return $namespaces;
    }

    public function storageclasses()
    {
        $storageclasses = [];

        foreach (PsoStorageClass::items(PsoStorageClass::PREFIX, 'name') as $storageclass)
        {
            $pure_size = 0;
            $pure_used = 0;
            $pure_volumes = 0;

            foreach (PsoPersistentVolumeClaim::items(PsoPersistentVolumeClaim::PREFIX, 'uid') as $uid) {
                $myvol = new PsoPersistentVolumeClaim($uid);

                if ($myvol->storageClass == $storageclass)
                {
                    $pure_size = $pure_size + $myvol->pure_size;
                    $pure_used = $pure_used + $myvol->pure_used;
                    $pure_volumes = $pure_volumes + 1;
                }
            }

            $storageclass_info = new PsoStorageClass($storageclass);
            $storageclass_info->size = $pure_size;
            $storageclass_info->sizeFormatted = $this->formatBytes($pure_size, 2);
            $storageclass_info->used = $pure_used;
            $storageclass_info->usedFormatted = $this->formatBytes($pure_used, 2);
            $storageclass_info->volumeCount = $pure_volumes;

            array_push($storageclasses, $storageclass_info->asArray());
        }
        return $storageclasses;
    }

    public function labels()
    {
        $labels = [];
        $pure_storageclasses = PsoStorageClass::items(PsoStorageClass::PREFIX, 'name');

        foreach (PsoPersistentVolumeClaim::items(PsoPersistentVolumeClaim::PREFIX, 'labels') as $label) {
            $pure_size = 0;
            $pure_used = 0;
            $pure_volumes = 0;
            $storageclasses = [];

            foreach (PsoPersistentVolumeClaim::items(PsoPersistentVolumeClaim::PREFIX, 'uid') as $uid) {
                $myvol = new PsoPersistentVolumeClaim($uid);

                if (is_array($myvol->labels)) {
                    if (in_array($label, $myvol->labels) and in_array($myvol->storageClass, $pure_storageclasses))
                    {
                        $pure_size = $pure_size + $myvol->pure_size;
                        $pure_used = $pure_used + $myvol->pure_used;
                        $pure_volumes = $pure_volumes + 1;
                        if (!in_array($myvol->storageClass, $storageclasses)) array_push($storageclasses, $myvol->storageClass);
                    }
                }
            }

            if ($pure_volumes > 0) {
                $label_info = new PsoLabels(PsoLabels::PREFIX, $label);
                if ($label !== '') {
                    $label_info->label = $label;
                    $label_info->key = explode('=', $label)[0];
                    $label_info->value = explode('=', $label)[1];
                } else {
                    $label_info->label = '';
                    $label_info->key = '';
                    $label_info->value = '';
                }
                $label_info->size = $pure_size;
                $label_info->sizeFormatted = $this->formatBytes($pure_size, 2);
                $label_info->used = $pure_used;
                $label_info->usedFormatted = $this->formatBytes($pure_used, 2);
                $label_info->volumeCount = $pure_volumes;
                $label_info->storageClasses = implode(',', $storageclasses);

                array_push($labels, $label_info->asArray());
            }
        }

        return $labels;
    }

    public function deployments()
    {
        $deployments = [];

        foreach (PsoDeployment::items(PsoDeployment::PREFIX, 'uid') as $deployment_uid)
        {
            $deployment = new PsoDeployment($deployment_uid);

            $pure_size = 0;
            $pure_used = 0;
            $pure_volumes = 0;
            $storageclasses = [];

            foreach (PsoPersistentVolumeClaim::items(PsoPersistentVolumeClaim::PREFIX, 'uid') as $uid) {
                $myvol = new PsoPersistentVolumeClaim($uid);

                if ((in_array($myvol->namespace . ':' . $myvol->name, $deployment->namespace_volnames)))
                {
                    $pure_size = $pure_size + $myvol->pure_size;
                    $pure_used = $pure_used + $myvol->pure_used;
                    $pure_volumes = $pure_volumes + 1;
                    if (!in_array($myvol->storageClass, $storageclasses)) array_push($storageclasses, $myvol->storageClass);
                }

            }

            $deployment->size = $pure_size;
            $deployment->sizeFormatted = $this->formatBytes($pure_size, 2);
            $deployment->used = $pure_used;
            $deployment->usedFormatted = $this->formatBytes($pure_used, 2);
            $deployment->volumeCount = $pure_volumes;
            $deployment->storageClasses = implode(',', $storageclasses);

            array_push($deployments, $deployment->asArray());
        }

        return $deployments;
    }

    public function statefulsets()
    {
        $statefulsets = [];
        foreach (PsoStatefulSet::items(PsoStatefulSet::PREFIX, 'uid') as $statefulset_uid)
        {
            $myset = new PsoStatefulSet($statefulset_uid);

            $pure_size = 0;
            $pure_used = 0;
            $pure_volumes = 0;
            $storageclasses = [];

            foreach (PsoPersistentVolumeClaim::items(PsoPersistentVolumeClaim::PREFIX, 'uid') as $uid) {
                $myvol = new PsoPersistentVolumeClaim($uid);

                if ((in_array($myvol->namespace . ':' . $myvol->name, $myset->namespace_volnames)))
                {
                    $pure_size = $pure_size + $myvol->pure_size;
                    $pure_used = $pure_used + $myvol->pure_used;
                    $pure_volumes = $pure_volumes + 1;
                    if (!in_array($myvol->storageClass, $storageclasses)) array_push($storageclasses, $myvol->storageClass);
                }

            }

            $myset->size = $pure_size;
            $myset->sizeFormatted = $this->formatBytes($pure_size, 2);
            $myset->used = $pure_used;
            $myset->usedFormatted = $this->formatBytes($pure_used, 2);
            $myset->volumeCount = $pure_volumes;
            $myset->storageClasses = implode(',', $storageclasses);

            array_push($statefulsets, $myset->asArray());
        }

        return $statefulsets;
    }

    public function portal_info()
    {
        $portal_info['total_used'] = $this->formatBytes($this->pso_info->totalused);
        $portal_info['total_size'] = $this->formatBytes($this->pso_info->totalsize);
        $portal_info['total_used_raw'] = $this->pso_info->totalused;
        $portal_info['total_size_raw'] = $this->pso_info->totalsize;
        $portal_info['last_refesh'] = Redis::get(self::VALID_PSO_DATA_KEY);

        $vols = [];
        foreach (PsoPersistentVolumeClaim::items(PsoPersistentVolumeClaim::PREFIX, 'uid') as $uid) {
            $volume = new PsoPersistentVolumeClaim($uid);

            if ($volume->pure_orphaned == null) {
                $vol['uid'] = $uid;
                $vol['name'] = $volume->name;;
                $vol['size'] = $volume->pure_size;
                $vol['sizeFormatted'] = $volume->pure_sizeFormatted;
                $vol['used'] = $volume->pure_used;
                $vol['usedFormatted'] = $volume->pure_usedFormatted;
                $vol['growth'] = $volume->pure_used - $volume->pure_24h_historic_used;
                $vol['growthFormatted'] = $this->formatBytes($volume->pure_used - $volume->pure_24h_historic_used, 2);
                if (isset($volume->pure_size)) {
                    $vol['growthPercentage'] = ($volume->pure_used - $volume->pure_24h_historic_used)/$volume->pure_size;
                } else {
                    $vol['growthPercentage'] = 0;
                }

                array_push($vols, $vol);
            }
        }

        $uids = array_column($vols, 'uid');
        $growths = array_column($vols, 'growthPercentage');

        array_multisort($growths, SORT_DESC, $uids, SORT_DESC, $vols);

        $portal_info['total_iops_read'] = $this->pso_info->total_iops_read;
        $portal_info['total_iops_write'] = $this->pso_info->total_iops_write;
        $portal_info['total_bw'] = $this->formatBytes($this->pso_info->total_bw_read + $this->pso_info->total_bw_write);
        $portal_info['total_bw_read'] = $this->formatBytes($this->pso_info->total_bw_read);
        $portal_info['total_bw_write'] = $this->formatBytes($this->pso_info->total_bw_write);

        $portal_info['low_msec_read'] = round($this->pso_info->low_msec_read, 2);
        $portal_info['low_msec_write'] = round($this->pso_info->low_msec_write, 2);
        $portal_info['high_msec_read'] = round($this->pso_info->high_msec_read, 2);
        $portal_info['high_msec_write'] = round($this->pso_info->high_msec_write, 2);

        $portal_info['top10_growth_vols'] = array_slice($vols, 0, 10);

        return $portal_info;
    }
}
