<?php

namespace App;

use App\Api\FlashArrayApi;
use App\Api\FlashBladeApi;
use App\Api\k8s\PodLog;
use App\Api\k8s\VolumeSnapshotClass;
use App\Api\k8s\VolumeSnapshot;
use App\Http\Classes\PsoArray;
use App\Http\Classes\PsoDeployment;
use App\Http\Classes\PsoInformation;
use App\Http\Classes\PsoJob;
use App\Http\Classes\PsoLabels;
use App\Http\Classes\PsoNamespace;
use App\Http\Classes\PsoNode;
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
use Kubernetes\Model\Io\K8s\Apimachinery\Pkg\Apis\Meta\V1\APIResource;
use Kubernetes\Model\Io\K8s\Api\Apps\V1\StatefulSetList;
use Kubernetes\Model\Io\K8s\Api\Apps\V1\DeploymentList;
use Kubernetes\Model\Io\K8s\Api\Core\V1\Container;
use Kubernetes\Model\Io\K8s\Api\Core\V1\EnvVar;
use Kubernetes\Model\Io\K8s\Api\Core\V1\PersistentVolumeList;
use Kubernetes\Model\Io\K8s\Api\Core\V1\Volume;
use Kubernetes\Model\Io\K8s\Api\Storage\V1\StorageClassList;
use Monolog\Handler\IFTTTHandler;

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
        $this->refreshTimeout = env('PSO_REFRESH_TIMEOUT', '300');

        // Initialize the psoInfo variable
        $this->psoInfo = new PsoInformation();
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

    /**
     * Connect to Kubernetes to find PSO namespace and prefix
     * and collect information about K8S PODs
     *
     * @return boolean
     */
    private function getPsoDetails()
    {
        // Log function call
        Log::debug('    Call getPsoDetails()');

        // Initialize variables
        $this->psoFound = false;

        // Try to connect to Kubernetes cluster API, catch any curl errors
        // Using a custom timeout for the CURL request, so we don't timeout our session
        try {
            Client::configure($this->master, $this->authentication, ['timeout' => 10]);
            $pod = new Pod();
            $pod_list = $pod->list('');
        } catch (Exception $e) {
            // Log error message
            Log::debug('xxx Error connecting to Kubernetes API at "' . $this->master . '"');
            Log::debug('    - Message: "' . $e->getMessage() . '"');
            Log::debug('    - File: "' . $e->getFile() . '"');
            Log::debug('    - Line: "' . $e->getLine() . '"');

            // If we catch a CURL error, return an error message
            $this->errorSource = 'k8s';
            $this->errorMessage = $e->getMessage();
            unset($e);
            return false;
        }

        // If the CURL connection was successful, check if the response was also successful
        // This could be an authentication error for example
        if (isset($pod_list->status)) {
            if ($pod_list->status == 'Failure') {
                // Log error message
                Log::debug('xxx Error connecting to Kubernetes API at "' . $this->master . '"');

                // If status is set to Failure, we hit an error, so we return an error message
                $this->errorSource = 'k8s';
                $this->errorMessage = $pod_list->message ?? 'Unknown error occurred';
                return false;
            }
        }

        // Loop through the POD's to find PSO namespace and prefix and store pods with PVC's
        $images = [];
        foreach (($pod_list->items ?? []) as $item) {
            $my_pod = null;
            $myPodName = $item->metadata->name ?? 'Unknown';
            $myPodNamespace = $item->metadata->namespace ?? 'Unknown';

            foreach (($item->spec->volumes ?? []) as $volume) {
                if ($volume->persistentVolumeClaim !== null) {
                    $my_pod = new PsoPod($item->metadata->uid);
                    $my_pod->name = $myPodName;
                    $my_pod->namespace = $myPodNamespace;
                    $my_pod->creationTimestamp = $item->metadata->creationTimestamp ?? '';
                    $my_pod->status = $item->status->phase ?? '';

                    if ($my_pod->labels == null) {
                        foreach (($item->metadata->labels ?? []) as $key => $value) {
                            $my_pod->array_push('labels', $key . '=' . $value);
                        }
                    }
                    $containers = [];
                    foreach (($item->spec->containers ?? []) as $container) {
                        array_push($containers, ($container->name ?? 'Unknown container name') . ': ' .
                            ($container->image  ?? 'Unknown container image'));
                    }
                    $my_pod->containers = $containers;

                    $myClaimName = ($volume->persistentVolumeClaim->claimName ?? 'Unknown');
                    $my_pod->array_push('pvc_name', $myClaimName);
                    $myNamespaceName = $my_pod->namespace . ':' . $myClaimName;
                    $my_pod->array_push('pvc_namespace_name', $myNamespaceName);
                }
            }

            foreach (($item->spec->containers ?? []) as $container) {
                foreach (($container->env ?? []) as $env) {
                    switch ($env->name) {
                        case 'PURE_K8S_NAMESPACE':
                            // If PSO is found, set psoFound to true and store prefix and namespace in Redis
                            $this->psoFound = true;
                            $this->psoInfo->prefix = $env->value ?? 'Unknown';
                            $this->psoInfo->namespace = $myPodNamespace ?? 'Unknown';
                            break;
                        case 'PURE_FLASHARRAY_SAN_TYPE':
                            $this->psoInfo->san_type = $env->value ?? '';
                            break;
                        case 'PURE_DEFAULT_BLOCK_FS_TYPE':
                            $this->psoInfo->block_fs_type = $env->value ?? '';
                            break;
                        case 'PURE_DEFAULT_BLOCK_FS_OPT':
                            $this->psoInfo->block_fs_opt = $env->value ?? '';
                            break;
                        case 'PURE_DEFAULT_BLOCK_MNT_OPT':
                            $this->psoInfo->block_mnt_opt = $env->value ?? '';
                            break;
                        case 'PURE_ISCSI_LOGIN_TIMEOUT':
                            $this->psoInfo->iscsi_login_timeout = $env->value ?? '';
                            break;
                        case 'PURE_ISCSI_ALLOWED_CIDRS':
                            $this->psoInfo->iscsi_allowed_cidrs = $env->value ?? '';
                            break;
                    }
                }

                if (
                    ($myPodName == 'pso-csi-controller-0') or
                        ($this->startsWith('pure-provisioner', $myPodName))
                ) {
                    $this->psoInfo->provisioner_pod = $myPodName;
                    $this->psoInfo->provisioner_container = $item->spec->containers[0]->name ?? 'Unknown';

                    $myContainerName = $container->name ?? 'Unknown container name';
                    if (($myContainerName == 'pso-csi-container') or ($myContainerName == 'pure-csi-container')) {
                        array_push($images, $container->name . ': ' . $container->image);
                    }
                    if (($myContainerName == 'csi-provisioner') or ($myContainerName == 'pure-provisioner')) {
                        array_push($images, $container->name . ': ' . $container->image);
                        foreach (($container->args ?? []) as $arg) {
                            if (strpos($arg, '--feature-gates=') !== false) {
                                $this->psoInfo->array_push('pso_args', str_replace('--feature-gates=', '', $arg));
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
                }
            }
        }
        $this->psoInfo->images = $images;

        if (!$this->psoFound) {
            // Log error message
            Log::debug('xxx Error unable to find PSO instance "' . $this->master . '"');

            // If PSO was not found, return an error
            $this->errorSource = 'pso';
            $this->errorMessage = 'Unable to find PSO namespace';
            return false;
        } else {
            return true;
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
        Log::debug('    Call getArrayInfo()');

        // Get the PSO secret from Kubernetes to retrieve the MgmtEndPoint and APIToken
        Client::configure($this->master, $this->authentication);
        $secret = new Secret($this->psoInfo->namespace);

        $pso_config = $secret->read($this->psoInfo->namespace, 'pure-provisioner-secret');
        if (isset($pso_config->code)) {
            $this->psoFound = false;
            $this->errorSource = 'k8s';
            $this->errorMessage = 'Unable to read (get) the PSO secret. ' .
                'Check the ClusterRoles and ClusterRoleBindings.';
            return false;
        }

        if (isset($pso_config->data)) {
            $pso_secret_data = $pso_config->data;
            $pso_config = json_decode(base64_decode($pso_secret_data['pure.json'], true));
        } else {
            $this->psoFound = false;
            $this->errorSource = 'k8s';
            $this->errorMessage = 'Unexpected error while getting PSO secrets. ' .
                'Check the ClusterRoles and ClusterRoleBindings.';
            return false;
        }

        $pso_yaml = $this->objectToArray($pso_config);
        if ($pso_yaml !== []) {
            $myYaml = [];
            foreach ($pso_yaml as $item) {
                $myYaml = array_merge($myYaml, $item);
            }
            $this->psoInfo->yaml = yaml_emit(["arrays" => $myYaml]);
        }

        // Get FlashArray™ information
        foreach (($pso_config->FlashArrays ?? []) as $flasharray) {
            $mgmtEndPoint = $flasharray->MgmtEndPoint ?? 'not set';
            $apiToken = $flasharray->APIToken ?? 'not set';

            if (($mgmtEndPoint !== 'not set') and ($apiToken !== 'not set')) {
                $newArray = new PsoArray($mgmtEndPoint);
                $newArray->apiToken = $apiToken;
                foreach (($flasharray->Labels ?? []) as $key => $value) {
                    $newArray->array_push('labels', $key . '=' . $value);
                }

                $fa_api = new FlashArrayApi();
                try {
                    // Connect to the array for the array name
                    $fa_api->authenticate($mgmtEndPoint, $apiToken);
                    $array_details = $fa_api->GetArray();
                    $model_details = $fa_api->GetArray('controllers=true');

                    $newArray->name = $array_details['array_name'];
                    $newArray->version = 'Purity//FA ' . $array_details['version'];
                    $newArray->model = 'Pure Storage® FlashArray™ ' . $model_details[0]['model'];

                    $port_details = $fa_api->GetPort();

                    foreach (($port_details  ?? []) as $port_detail) {
                        if (isset($port_detail['iqn']) and !in_array('iSCSI', ($newArray->protocols ?? []))) {
                            $newArray->array_push('protocols', 'iSCSI');
                        }
                        if (isset($port_detail['wwn']) and !in_array('FC', ($newArray->protocols ?? []))) {
                            $newArray->array_push('protocols', 'FC');
                        }
                        if (isset($port_detail['nqn']) and !in_array('NVMe', ($newArray->protocols ?? []))) {
                            $newArray->array_push('protocols', 'NVMe');
                        }
                    }
                    $newArray->message = '';
                } catch (Exception $e) {
                    // Log error message
                    Log::debug('xxx Error connecting to FlashArray™ "' . $mgmtEndPoint . '"');
                    Log::debug('    - Message: "' . $e->getMessage() . '"');
                    Log::debug('    - File: "' . $e->getFile() . '"');
                    Log::debug('    - Line: "' . $e->getLine() . '"');

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
        foreach (($pso_config->FlashBlades ?? []) as $flashblade) {
            $mgmtEndPoint = $flashblade->MgmtEndPoint ?? 'not set';
            $apiToken = $flashblade->APIToken ?? 'not set';
            $nfsEndPoint = $flashblade->NFSEndPoint ?? 'not set';

            if (($mgmtEndPoint !== 'not set') and ($apiToken !== 'not set') and ($nfsEndPoint !== 'not set')) {
                $newArray = new PsoArray($mgmtEndPoint);
                $newArray->apiToken = $apiToken;
                $myLabels = [];
                foreach (($flashblade->Labels  ?? []) as $key => $value) {
                    array_push($myLabels, $key . '=' . $value);
                }
                $newArray->labels = $myLabels;

                $fb_api = new FlashBladeApi($mgmtEndPoint, $apiToken);
                try {
                    // Connect to the array for the array name
                    $fb_api->authenticate();
                    $array = $fb_api->GetArray();

                    $newArray->name = $array['items'][0]['name'];
                    $newArray->model = 'Pure Storage® FlashBlade®';
                    $newArray->version = $array['items'][0]['os'] . ' ' . $array['items'][0]['version'];
                    $newArray->protocols = ['NFS', 'S3'];
                } catch (Exception $e) {
                    // Log error message
                    Log::debug('xxx Error connecting to FlashBlade® "' . $mgmtEndPoint . '"');
                    Log::debug('    - Message: "' . $e->getMessage() . '"');
                    Log::debug('    - File: "' . $e->getFile() . '"');
                    Log::debug('    - Line: "' . $e->getLine() . '"');

                    $newArray->name = $mgmtEndPoint;
                    $newArray->model = 'Offline';
                    $newArray->offline = $mgmtEndPoint;
                    $newArray->message = 'Unable to connect to FlashBlade® (' . $e->getMessage() . ')';
                    unset($e);
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
                    } else {
                        $newArray->message = 'No NFSEndPoint was set for this FlashBlade®. ' .
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
     * Connect to Kubernetes to retrieve the StorageClasses
     *
     * @return boolean
     */
    private function getStorageClasses()
    {
        // Log function call
        Log::debug('    Call getStorageClasses()');

        // Retrieve all Kubernetes StorageClasses for this cluster
        Client::configure($this->master, $this->authentication);
        $storageclass = new StorageClass();
        $storageclass_list = $storageclass->list();

        if (isset($storageclass_list->code)) {
            $this->psoFound = false;
            $this->errorSource = 'k8s';
            $this->errorMessage = 'Unable to list StorageClasses. Check the ClusterRoles and ClusterRoleBindings.';
            return false;
        }

        foreach (($storageclass_list->items ?? []) as $item) {
                // Add all storageclasses that use PSO
            if (in_array(($item->provisioner ?? ''), self::PURE_PROVISIONERS)) {
                $mystorageclass = new PsoStorageClass($item->metadata->name ?? $item->metadata->uid);

                $parameters = [];
                foreach (($item->parameters ?? []) as $key => $value) {
                    array_push($parameters, $key . '=' . $value);
                }
                $mystorageclass->parameters = $parameters;

                $mountOptions = [];
                foreach (($item->mountOptions ?? []) as $key => $value) {
                    // key is only the array counter
                    array_push($mountOptions, $value);
                }
                $mystorageclass->mountOptions = $mountOptions ?? '';

                $mystorageclass->allowVolumeExpansion = $item->allowVolumeExpansion ?? '';
                $mystorageclass->volumeBindingMode = $item->volumeBindingMode ?? '';
                $mystorageclass->reclaimPolicy = $item->reclaimPolicy ?? '';

                $mystorageclass->isDefaultClass = false;
                foreach (($item->metadata->annotations ?? []) as $key => $value) {
                    if (('storageclass.kubernetes.io/is-default-class' == $key) and ($value == 'true')) {
                        $mystorageclass->isDefaultClass = true;
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
        Log::debug('    Call getPersistentVolumeClaims()');

        // Retrieve all Kubernetes PVC's for this cluster
        Client::configure($this->master, $this->authentication);
        $pvc = new PersistentVolumeClaim();
        $pvc_list = $pvc->list('');

        if (isset($pvc_list->code)) {
            $this->psoFound = false;
            $this->errorSource = 'k8s';
            $this->errorMessage = 'Unable to list Persistent Volume Claims. ' .
                'Check the ClusterRoles and ClusterRoleBindings.';
            return false;
        }

        foreach (($pvc_list->items ?? []) as $item) {
            $myvol = new PsoPersistentVolumeClaim($item->metadata->uid);
            $myvol->name = $item->metadata->name ?? 'Unknown';
            $myvol->namespace = $item->metadata->namespace ?? 'Unknown';
            $myvol->namespace_name = $myvol->namespace . ':' . $myvol->name;
            $myvol->size = $item->spec->resources->requests['storage'] ?? '';
            $myvol->storageClass =  $item->spec->storageClassName ?? '';
            $myvol->status = $item->status->phase ?? '';
            $myvol->creationTimestamp = $item->metadata->creationTimestamp ?? '';

            $labels = [];
            foreach (($item->metadata->labels ?? []) as $key => $value) {
                array_push($labels, $key . '=' . $value);
            }
            $myvol->labels = $labels;

            $myvol->pv_name = $item->spec->volumeName ?? '';
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
        Log::debug('    Call getStatefulsets()');

        // Retrieve all Kubernetes StatefulSets for this cluster
        Client::configure($this->master, $this->authentication);
        $statefulset = new StatefulSet();
        $statefulset_list = $statefulset->list('');

        if (isset($statefulset_list->code)) {
            $this->psoFound = false;
            $this->errorSource = 'k8s';
            $this->errorMessage = 'Unable to list StatefulSets. Check the ClusterRoles and ClusterRoleBindings.';
            return false;
        }

        foreach (($statefulset_list->items ?? []) as $item) {
            if (isset($item->spec->volumeClaimTemplates)) {
                $namespace_names = [];
                foreach (($item->spec->volumeClaimTemplates ?? []) as $template) {
                    for ($i = 0; $i < $item->spec->replicas; $i++) {
                        $myStorageClasses = PsoStorageClass::items(PsoStorageClass::PREFIX, 'name');
                        if (
                            in_array(($template->spec->storageClassName), $myStorageClasses)
                            or $template->spec->storageClassName == null
                        ) {
                            array_push($namespace_names, $item->metadata->namespace . ':' .
                                $template->metadata->name . '-' . $item->metadata->name . '-' . $i);
                        }
                    }
                }

                if ($namespace_names !== []) {
                    $myset = new PsoStatefulSet($item->metadata->uid);
                    $myset->name = $item->metadata->name ?? 'Unknown';
                    $myset->namespace = $item->metadata->namespace ?? 'Unknown';
                    $myset->namespace_names = $namespace_names;
                    $myset->creationTimestamp = $item->metadata->creationTimestamp ?? '';
                    $myset->replicas = $item->spec->replicas ?? '';

                    $labels = [];
                    foreach (($item->metadata->labels ?? []) as $key => $value) {
                        array_push($labels, $key . '=' . $value);
                    }
                    $myset->labels = $labels;
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
        Log::debug('    Call getDeployments()');

        // Retrieve all Kubernetes StatefulSets for this cluster
        Client::configure($this->master, $this->authentication);
        $deployment = new Deployment();
        $deployment_list = $deployment->list('');

        if (isset($deployment_list->code)) {
            $this->psoFound = false;
            $this->errorSource = 'k8s';
            $this->errorMessage = 'Unable to list Deployments. Check the ClusterRoles and ClusterRoleBindings.';
            return false;
        }

        foreach (($deployment_list->items ?? []) as $item) {
            foreach (($item->spec->template->spec->volumes ?? []) as $vol) {
                if (isset($vol->persistentVolumeClaim->claimName)) {
                    $mynamespace_name = ($item->metadata->namespace ?? 'Unknown') . ':' .
                        ($vol->persistentVolumeClaim->claimName  ?? 'Unknown');
                    $myPvcs = PsoPersistentVolumeClaim::items(
                        PsoPersistentVolumeClaim::PREFIX,
                        'namespace_name'
                    );

                    if (in_array($mynamespace_name, $myPvcs)) {
                        $mydeployment = new PsoDeployment($item->metadata->uid);
                        $mydeployment->name = $item->metadata->name ?? 'Unknown';
                        $mydeployment->namespace = $item->metadata->namespace ?? 'Unknown';
                        $mydeployment->creationTimestamp = $item->metadata->creationTimestamp ?? '';

                        $mydeployment->volumeCount = $mydeployment->volumeCount + 1;

                        $namespace_name = $mydeployment->namespace_names;
                        if ($namespace_name == null) {
                            $mydeployment->namespace_names = [$mynamespace_name];
                        } else {
                            array_push($namespace_name, $mynamespace_name);
                            $mydeployment->namespace_names = $namespace_name;
                        }

                        $mydeployment->replicas = $item->spec->replicas ?? '';

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
        Log::debug('    Call getJobs()');

        // Retrieve all Kubernetes StatefulSets for this cluster
        Client::configure($this->master, $this->authentication);
        $job = new Job();
        $job_list = $job->list('');

        if (isset($job_list->code)) {
            $this->psoFound = false;
            $this->errorSource = 'k8s';
            $this->errorMessage = 'Unable to list Jobs. Check the ClusterRoles and ClusterRoleBindings.';
            return false;
        }

        foreach (($job_list->items ?? []) as $item) {
            foreach (($item->spec->template->spec->volumes ?? []) as $volume) {
                $mynamespace_name = ($item->metadata->namespace ?? 'Unknown') . ':' .
                    ($volume->persistentVolumeClaim->claimName ?? 'Unknown');
                $myPvcs = PsoPersistentVolumeClaim::items(PsoPersistentVolumeClaim::PREFIX, 'namespace_name');
                if (in_array($mynamespace_name, $myPvcs)) {
                    $my_job = new PsoJob($item->metadata->uid);
                    $my_job->name = $item->metadata->name ?? 'Unknown';
                    $my_job->namespace = $item->metadata->namespace ?? 'Unknown';
                    $my_job->creationTimestamp = $item->metadata->creationTimestamp ?? '';

                    if ($item->status->active == 1) {
                        $my_job->status = 'Running';
                    } elseif ($item->status->succeeded == 1) {
                        $my_job->status = 'Completed';
                    } elseif ($item->status->failed == 1) {
                        $my_job->status = 'Failed';
                    } else {
                        $my_job->status = 'Unknown';
                    }

                    if ($my_job->labels == null) {
                        foreach (($item->metadata->labels ?? []) as $key => $value) {
                            $my_job->array_push('labels', $key . '=' . $value);
                        }
                    }

                    $my_job->array_push('pvc_name', $volume->persistentVolumeClaim->claimName ?? 'not set');
                    $my_job->array_push('pvc_namespace_name', ($item->metadata->namespace ?? 'Unknown') . ':' .
                        ($volume->persistentVolumeClaim->claimName ?? 'Unknown'));
                }
            }
        }
        return true;
    }

    /**
     * Connect to Kubernetes to retrieve the Nodes
     *
     * @return boolean
     */
    private function getNodes()
    {
        // Log function call
        Log::debug('    Call getNodes()');

        // Retrieve all Kubernetes StatefulSets for this cluster
        Client::configure($this->master, $this->authentication);
        $node = new Node();
        $node_list = $node->list();

        if (isset($node_list->code)) {
            $this->psoFound = false;
            $this->errorSource = 'k8s';
            $this->errorMessage = 'Unable to list Nodes. Check the ClusterRoles and ClusterRoleBindings.';
            return false;
        }

        foreach (($node_list->items ?? []) as $item) {
            $mynode = new PsoNode($item->metadata->uid);

            $mynode->name = $item->metadata->name ?? $item->metadata->uid;
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
                switch ($address->type) {
                    case 'Hostname':
                        $mynode->hostname = $address->address ?? '';
                        break;
                    case 'InternalIP':
                        $mynode->InternalIP = $address->address ?? '';
                        break;
                }
            }

            $conditions = [];
            foreach (($item->status->conditions ?? []) as $condition) {
                if ($condition->status == 'True') {
                    array_push($conditions, ($condition->type ?? ''));
                }
            }
            $mynode->condition = $conditions;
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
        Log::debug('    Call getVolumeSnapshotClasses()');

        // Get API version for snapshot.storage.k8s.io
        $this->psoInfo->snapshot_api_version = '';
        Client::configure($this->master, $this->authentication, ['timeout' => 10]);
        $api = new APIService();
        $api_list = $api->list();

        foreach (($api_list->items ?? []) as $api_resource) {
            if ($api_resource->spec->group == 'snapshot.storage.k8s.io') {
                // Get API version (v1alpha1 or v1beta1) for the `snapshot.storage.k8s.io` API
                $this->psoInfo->snapshot_api_version = $api_resource->spec->version ?? 'Unknown';
            }
        }
        Client::configure($this->master, $this->authentication, ['timeout' => 10]);
        $class = new VolumeSnapshotClass();

        try {
            if ($this->psoInfo->snapshot_api_version == 'v1alpha1') {
                $snapshotter_name = 'snapshotter';
                $reclaim_name = 'reclaimPolicy';
                $class_list = $class->listV1alpha1();
            } else {
                // Default to v1beta1
                $snapshotter_name = 'driver';
                $reclaim_name = 'deletionPolicy';
                $class_list = $class->listV1beta1();
            }

            if (isset($class_list->code)) {
                // If we cannot select the API version or an error is returned, we will abort
                // However we will not return an error, since snapshots suppport is optional
                Log::debug('xxx Unable to access the VolumeSnapshotClasses API.');
                return false;
            }

            foreach (($class_list['items'] ?? []) as $item) {
                if (in_array($item[$snapshotter_name], self::PURE_PROVISIONERS)) {
                    $snapshotclass = new PsoVolumeSnapshotClass($item['metadata']['name'] ?? 'Unknown');

                    $snapshotclass->snapshotter = $item[$snapshotter_name] ?? '';
                    $snapshotclass->reclaimPolicy = $item[$reclaim_name] ?? '';

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
            Log::debug('xxx Error retrieving VolumeSnapshotClasses using API version ' .
                $this->psoInfo->snapshot_api_version);
            Log::debug('    - Message: "' . $e->getMessage() . '"');
            Log::debug('    - File: "' . $e->getFile() . '"');
            Log::debug('    - Line: "' . $e->getLine() . '"');
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
        Log::debug('    Call getVolumeSnapshots()');

        Client::configure($this->master, $this->authentication, ['timeout' => 10]);
        $snap = new VolumeSnapshot();


        try {
            if ($this->psoInfo->snapshot_api_version == 'v1alpha1') {
                $snap_list = $snap->listV1alpha1('');
            } else {
                $snap_list = $snap->listV1beta1('');
            }

            if (isset($snap_list->code)) {
                // Do not return an error if not found, since this is a feature gate that might not be enabled.
                return false;
            }

            foreach (($snap_list['items'] ?? []) as $item) {
                if ($this->psoInfo->snapshot_api_version == 'v1alpha1') {
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

                    if ($this->psoInfo->snapshot_api_version == 'v1alpha1') {
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
                    $volumeSnapshot->pure_volname = $this->psoInfo->prefix . '-pvc-' . $uid;
                    if (isset($uid)) {
                        $pvc = new PsoPersistentVolumeClaim($uid);
                        $pvc->has_snaps = true;
                    }
                }
            }
        } catch (Exception $e) {
            // Log error message
            Log::debug('xxx Error retrieving VolumeSnapshots using API version ' .
                $this->psoInfo->snapshot_api_version);
            Log::debug('    - Message: "' . $e->getMessage() . '"');
            Log::debug('    - File: "' . $e->getFile() . '"');
            Log::debug('    - Line: "' . $e->getLine() . '"');
            unset($e);
        }
    }

    /**
     * Connect to the Pure Storage® arrays to retrieve volume information
     *
     * @return boolean
     */
    private function addArrayVolumeInfo()
    {
        // Log function call
        Log::debug('    Call addArrayVolumeInfo()');

        $total_size = 0;
        $total_used = 0;
        $total_orphaned_used = 0;
        $total_snapshot_used = 0;

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
                $fa_api = new FlashArrayApi();
                $fa_api->authenticate($array->mgmtEndPoint, $array->apiToken);

                // TODO: Can we also add Cockraoch DB volumes prefix . '-pso-db_' . <number>
                $vols = $fa_api->GetVolumes([
                    'names' => $this->psoInfo->prefix . '-pvc-*',
                    'space' => 'true',
                ]);

                foreach (($vols ?? []) as $vol) {
                    if ($this->startsWith($this->psoInfo->prefix . '-pvc-', $vol['name'])) {
                        $uid = str_ireplace($this->psoInfo->prefix . '-pvc-', '', $vol['name']) ;

                        $myvol = new PsoPersistentVolumeClaim($uid);
                        $myvol->pure_name = $vol['name'] ?? '';
                        $myvol->pure_size = $vol['size'] ?? 0;
                        $myvol->pure_sizeFormatted = $this->formatBytes($myvol->pure_size, 2);
                        $myvol->pure_used = $vol['size'] * (1 - $vol['thin_provisioning'] ?? 0);
                        $myvol->pure_usedFormatted = $this->formatBytes($myvol->pure_used, 2);
                        $myvol->pure_drr = $vol['data_reduction'] ?? 1;
                        $myvol->pure_thinProvisioning = $vol['thin_provisioning'] ?? 0;
                        $myvol->pure_arrayName = $array->name;
                        $myvol->pure_arrayType = 'FA';
                        $myvol->pure_arrayMgmtEndPoint = $array->mgmtEndPoint;
                        $myvol->pure_snapshots = $vol['snapshots'] ?? 0;
                        $myvol->pure_volumes = $vol['volumes'] ?? 0;
                        $myvol->pure_sharedSpace = $vol['shared_space'] ?? 0;
                        $myvol->pure_totalReduction = $vol['total_reduction'] ?? 1;
                        if ($myvol->name == null) {
                            $myvol->pure_orphaned = $uid;
                            $myvol->pure_orphaned_state = 'Unmanaged by PSO';
                            $myvol->pure_orphaned_pvc_name = 'Not available for unmanaged PV\'s';
                            $myvol->pure_orphaned_pvc_namespace = 'Not available for unmanaged PV\'s';

                            $total_orphaned_used = $total_orphaned_used + ($vol['size'] ?? 0) *
                                (1 - ($vol['thin_provisioning'] ?? 0));
                        } else {
                            $total_used = $total_used + ($vol['size'] ?? 0)  *
                                (1 - ($vol['thin_provisioning'] ?? 0));
                            $total_size = $total_size + ($vol['size'] ?? 0);
                        }
                        $total_snapshot_used = $total_snapshot_used + ($vol['snapshots'] ?? 0);
                    }
                }

                $vols_perf = $fa_api->GetVolumes([
                    'names' => $this->psoInfo->prefix . '-pvc-*',
                    'action' => 'monitor',
                ]);

                foreach (($vols_perf ?? []) as $vol_perf) {
                    if ($this->startsWith($this->psoInfo->prefix . '-pvc-', $vol_perf['name'])) {
                        $uid = str_ireplace($this->psoInfo->prefix .
                            '-pvc-', '', $vol_perf['name']) ;

                        $myvol = new PsoPersistentVolumeClaim($uid);
                        $myvol->pure_reads_per_sec = $vol_perf['reads_per_sec'] ?? 0;
                        $myvol->pure_writes_per_sec = $vol_perf['writes_per_sec'] ?? 0;
                        $myvol->pure_input_per_sec = $vol_perf['input_per_sec'] ?? 0;
                        $myvol->pure_input_per_sec_formatted = $this->formatBytes(
                            $vol_perf['input_per_sec'],
                            1,
                            2
                        ) . '/s';
                        $myvol->pure_output_per_sec = $vol_perf['output_per_sec'] ?? 0;
                        $myvol->pure_output_per_sec_formatted = $this->formatBytes(
                            $vol_perf['output_per_sec'],
                            1,
                            2
                        ) . '/s';
                        $myvol->pure_usec_per_read_op = round(
                            $vol_perf['usec_per_read_op'] / 1000,
                            2,
                        );
                        $myvol->pure_usec_per_write_op = round(
                            $vol_perf['usec_per_write_op'] / 1000,
                            2,
                        );

                        $total_iops_read = $total_iops_read + $vol_perf['reads_per_sec'] ?? 0;
                        $total_iops_write = $total_iops_write + $vol_perf['writes_per_sec'] ?? 0;
                        $total_bw_read = $total_bw_read + $vol_perf['output_per_sec'] ?? 0;
                        $total_bw_write = $total_bw_write + $vol_perf['input_per_sec'] ?? 0;

                        if (($vol_perf['usec_per_read_op'] / 1000 < $low_msec_read) or ($low_msec_read = -1)) {
                            $low_msec_read = $vol_perf['usec_per_read_op'] / 1000;
                        }
                        if (($vol_perf['usec_per_write_op'] / 1000 < $low_msec_write) or ($low_msec_write = -1)) {
                            $low_msec_write = $vol_perf['usec_per_write_op'] / 1000;
                        }
                        if ($vol_perf['usec_per_read_op'] / 1000 > $high_msec_read) {
                            $high_msec_read = $vol_perf['usec_per_read_op'] / 1000;
                        }
                        if ($vol_perf['usec_per_write_op'] / 1000 > $high_msec_write) {
                            $high_msec_write = $vol_perf['usec_per_write_op'] / 1000;
                        }

                        $perf_count = $perf_count + 1;
                    }
                }

                try {
                    $vols_perf = $fa_api->GetVolumes([
                        'names' => $this->psoInfo->prefix . '-pvc-*',
                        'space' => 'true',
                        'historical' => '24h',
                    ]);

                    $vol_hist_size = [];
                    foreach (($vols_perf ?? []) as $vol_perf) {
                        if ($this->startsWith($this->psoInfo->prefix . '-pvc-', $vol_perf['name'])) {
                            $uid = str_ireplace($this->psoInfo->prefix . '-pvc-', '', $vol_perf['name']);

                            if (
                                isset($vol_hist_size[$uid]['first_date']) and
                                (strtotime($vol_perf['time']) < $vol_hist_size[$uid]['first_date'])
                            ) {
                                $vol_hist_size[$uid]['first_used'] = $vol_perf['total'];
                                $vol_hist_size[$uid]['first_date'] = strtotime($vol_perf['time']);
                            } elseif (!isset($vol_hist_size[$uid]['first_date'])) {
                                $vol_hist_size[$uid]['first_used'] = $vol_perf['total'];
                                $vol_hist_size[$uid]['first_date'] = strtotime($vol_perf['time']);
                            }
                        }
                    }

                    foreach (PsoPersistentVolumeClaim::items(PsoPersistentVolumeClaim::PREFIX, 'uid') as $uid) {
                        $vol = new PsoPersistentVolumeClaim($uid);

                        if (isset($vol_hist_size[$uid]['first_used']) and ($vol->pure_arrayType == 'FA')) {
                            $vol->pure_24h_historic_used = $vol_hist_size[$uid]['first_used'];
                        }
                    }
                } catch (Exception $e) {
                    // Log error message
                    Log::debug('xxx Error retrieving historical space usage for volumes.');
                    Log::debug('    - Message: "' . $e->getMessage() . '"');
                    Log::debug('    - File: "' . $e->getFile() . '"');
                    Log::debug('    - Line: "' . $e->getLine() . '"');
                    unset($e);
                }

                $snaps = $fa_api->GetVolumes([
                    'names' => $this->psoInfo->prefix . '-pvc-*',
                    'space' => 'true',
                    'snap' => 'true',
                ]);

                foreach (($snaps ?? []) as $snap) {
                    if ($this->startsWith($this->psoInfo->prefix . '-pvc-', $snap['name'])) {
                        $snap_prefix = '.snapshot-';
                        $pure_volname = substr($snap['name'], 0, strpos($snap['name'], $snap_prefix));
                        $uid = substr($snap['name'], strpos($snap['name'], $snap_prefix) + strlen($snap_prefix));

                        $mysnap = new PsoVolumeSnapshot($uid);
                        if (($mysnap->name == '') and ($mysnap->namespace == '') and ($mysnap->sourceName == '')) {
                            $mysnap->name = '';
                            $mysnap->namespace = 'Unknown';
                            $mysnap->sourceName = $pure_volname;
                            $mysnap->readyToUse = 'Ready';
                            $mysnap->errorMessage = 'This snapahot ia (no longer) managed by Kubernetes';
                            $mysnap->orphaned = $pure_volname;
                        }

                        $mysnap->pure_name = $snap['name'];
                        $mysnap->pure_volname = $pure_volname;
                        $mysnap->pure_size = $snap['size'] ?? 0;
                        $mysnap->pure_sizeFormatted = $this->formatBytes($mysnap->pure_size, 2);
                        ;
                        $mysnap->pure_used = $snap['total'] ?? 0;
                        $mysnap->pure_usedFormatted = $this->formatBytes($mysnap->pure_used, 2);
                        ;

                        $mysnap->pure_arrayName = $array->name;
                        $mysnap->pure_arrayType = 'FA';
                        $mysnap->pure_arrayMgmtEndPoint = $array->mgmtEndPoint;
                    }
                }
            } elseif (strpos($array->model, 'FlashBlade') and ($array->offline == null)) {
                $fb_api = new FlashBladeApi($array->mgmtEndPoint, $array->apiToken);

                try {
                    $fb_api->authenticate();

                    $filesystems = $fb_api->GetFileSystems([
                        'names' => $this->psoInfo->prefix . '-pvc-*',
                        'space' => 'true',
                        'destroyed' => false,
                    ]);
                } catch (Exception $e) {
                    // Log error message
                    Log::debug('xxx Error getting FileSystems for "' . $array->mgmtEndPoint . '"');
                    Log::debug('    - Message: "' . $e->getMessage() . '"');
                    Log::debug('    - File: "' . $e->getFile() . '"');
                    Log::debug('    - Line: "' . $e->getLine() . '"');

                    unset($e);
                    $filesystems = null;
                }

                foreach (($filesystems['items'] ?? []) as $filesystem) {
                    if ($this->startsWith($this->psoInfo->prefix . '-pvc-', $filesystem['name'])) {
                        $uid = str_ireplace($this->psoInfo->prefix . '-pvc-', '', $filesystem['name']) ;

                        $myvol = new PsoPersistentVolumeClaim($uid);
                        $myvol->pure_name = $filesystem['name'] ?? '';
                        $myvol->pure_size = $filesystem['provisioned'] ?? 0;
                        $myvol->pure_sizeFormatted = $this->formatBytes($myvol->pure_size, 2);
                        $myvol->pure_used = $filesystem['space']['virtual'] ?? 0;
                        $myvol->pure_usedFormatted = $this->formatBytes($myvol->pure_used, 2);
                        $myvol->pure_drr = $filesystem['space']['data_reduction'] ?? 1;
                        $myvol->pure_thinProvisioning = 0;
                        $myvol->pure_arrayName = $array->name;
                        $myvol->pure_arrayType = 'FB';
                        $myvol->pure_arrayMgmtEndPoint = $array->mgmtEndPoint;
                        $myvol->pure_snapshots = $filesystem['space']['snapshots'] ?? 0;
                        $myvol->pure_volumes = $filesystem['space']['unique'] ?? 0;
                        $myvol->pure_sharedSpace = 0;
                        $myvol->pure_totalReduction = $filesystem['space']['data_reduction'] ?? 1;
                        if ($myvol->name == null) {
                            $myvol->pure_orphaned = $uid;
                            $myvol->pure_orphaned_state = 'Unmanaged by PSO';
                            $myvol->pure_orphaned_pvc_name = 'Unknown';
                            $myvol->pure_orphaned_pvc_namespace = 'Unknown';

                            $total_orphaned_used = $total_orphaned_used + ($filesystem['space']['virtual'] ?? 0);
                        } else {
                            $total_used = $total_used + ($filesystem['space']['virtual'] ?? 0);
                            $total_size = $total_size + ($filesystem['provisioned'] ?? 0);
                        }
                        $total_snapshot_used = $total_snapshot_used + ($filesystem['space']['snapshots'] ?? 0);

                        $fs_perf = $fb_api->GetFileSystemsPerformance([
                            'names' => $filesystem['name'],
                            'protocol' => 'nfs',
                        ]);

                        foreach (($fs_perf['items'] ?? []) as $fs_perf_item) {
                            $myvol->pure_reads_per_sec = $fs_perf_item['reads_per_sec'] ?? 0;
                            $myvol->pure_writes_per_sec = $fs_perf_item['writes_per_sec'] ?? 0;
                            $myvol->pure_input_per_sec = $fs_perf_item['write_bytes_per_sec'] ?? 0;
                            $myvol->pure_input_per_sec_formatted = $this->formatBytes(
                                $myvol->pure_input_per_sec,
                                1,
                                2
                            ) . '/s';
                            $myvol->pure_output_per_sec = $fs_perf_item['read_bytes_per_sec'];
                            $myvol->pure_output_per_sec_formatted = $this->formatBytes(
                                $myvol->pure_output_per_sec,
                                1,
                                2
                            ) . '/s';
                            $myvol->pure_usec_per_read_op = round(
                                $myvol->pure_usec_per_read_op / 1000,
                                2
                            );
                            $myvol->pure_usec_per_write_op = round(
                                $myvol->pure_usec_per_write_op / 1000,
                                2
                            );

                            $total_iops_read = $total_iops_read + $myvol->pure_reads_per_sec;
                            $total_iops_write = $total_iops_write + $myvol->pure_writes_per_sec;
                            $total_bw_read = $total_bw_read + $myvol->pure_output_per_sec;
                            $total_bw_write = $total_bw_write + $myvol->pure_input_per_sec;

                            $perf_count = $perf_count + 1;
                        }
                    }
                }
            }
        }
        $this->psoInfo->totalsize = $total_size;
        $this->psoInfo->totalused = $total_used;
        $this->psoInfo->total_orphaned_used = $total_orphaned_used;
        $this->psoInfo->total_snapshot_used = $total_snapshot_used;

        $this->psoInfo->total_iops_read = $total_iops_read;
        $this->psoInfo->total_iops_write = $total_iops_write;
        $this->psoInfo->total_bw_read = $total_bw_read;
        $this->psoInfo->total_bw_write = $total_bw_write;

        if ($low_msec_read = -1) {
            $this->psoInfo->low_msec_read = 0;
        } else {
            $this->psoInfo->low_msec_read = $low_msec_read;
        }

        if ($low_msec_write = -1) {
            $this->psoInfo->low_msec_write = 0;
        } else {
            $this->psoInfo->low_msec_write = $low_msec_write;
        }

        $this->psoInfo->high_msec_read = $high_msec_read;
        $this->psoInfo->high_msec_write = $high_msec_write;
    }

    /**
     * Connect to Kubernetes to retrieve the PersistentVolumes
     *
     * @return boolean
     */
    private function getPersistentVolumes()
    {
        // Log function call
        Log::debug('    Call getPersistentVolumes()');

        // Retrieve all Kubernetes PVC's for this cluster
        Client::configure($this->master, $this->authentication);
        $pv = new PersistentVolume();
        $pv_list = $pv->list();

        if (isset($pv_list->code)) {
            $this->psoFound = false;
            $this->errorSource = 'k8s';
            $this->errorMessage = 'Unable to list Persistent Volumes (PV\'s). ' .
                'Check the ClusterRoles and ClusterRoleBindings.';
            return false;
        }

        foreach (($pv_list->items ?? []) as $item) {
            if (
                in_array(
                    $item->spec->storageClassName,
                    PsoStorageClass::items(
                        PsoStorageClass::PREFIX,
                        'name'
                    )
                ) and
                ($item->status->phase == 'Released')
            ) {
                $uid = str_replace('pvc-', '', ($item->metadata->name ?? 'Unknown'));

                $orphaned_list = PsoPersistentVolumeClaim::items(
                    PsoPersistentVolumeClaim::PREFIX,
                    'pure_orphaned'
                );
                if (in_array($uid, $orphaned_list)) {
                    $vol = new PsoPersistentVolumeClaim($uid);
                    $vol->pure_orphaned_state = 'Released PV';
                    $vol->pure_orphaned_pvc_name = $item->spec->claimRef->name ?? 'Unknown';
                    $vol->pure_orphaned_pvc_namespace = $item->spec->claimRef->namespace ?? 'Unknown';
                }
            }
        }
        return true;
    }

    /**
     * Remove the redis key which is used to determine is the data is stale
     * does not include the actual refresh
     *
     * @return boolean
     */
    public static function requestRefresh()
    {
        Log::debug('--- Data refresh scheduled');
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
            //config set stop-writes-on-bgsave-error no

            if ((Redis::get(self::VALID_PSO_DATA_KEY) !== null)) {
                $this->psoFound = true;
                $this->errorSource = '';
                $this->errorMessage = '';
                return true;
            }
        } catch (Exception $e) {
            // Log error message
            Log::debug('xxx Error connecting to Redis');
            Log::debug('    - Message: "' . $e->getMessage() . '"');
            Log::debug('    - File: "' . $e->getFile() . '"');
            Log::debug('    - Line: "' . $e->getLine() . '"');

            // If we catch a CURL error, return an error message
            $this->errorSource = 'redis';
            $this->errorMessage = 'Redis: ' . $e->getMessage();
            unset($e);
            return false;
        }

        if (Redis::get(self::PSO_UPDATE_KEY) !== null) {
            Log::debug('--- Already busy with refresh');
            $this->psoFound = false;
            $this->errorSource = 'refresh';
            $this->errorMessage = null;
            return true;
        } else {
            Log::debug('--- Start Refresh data');
            Redis::set(self::PSO_UPDATE_KEY, time());
            Redis::expire(self::PSO_UPDATE_KEY, 30);
        }

        // Remove stale PSO data from Redis
        Log::debug('    Remove stale data');
        Redis::del(self::VALID_PSO_DATA_KEY);
        PsoArray::deleteAll(PsoArray::PREFIX);
        PsoDeployment::DeleteAll(PsoDeployment::PREFIX);
        PsoInformation::deleteAll(PsoInformation::PREFIX);
        PsoLabels::deleteAll(PsoLabels::PREFIX);
        PsoNamespace::deleteAll(PsoNamespace::PREFIX);
        PsoPersistentVolumeClaim::deleteAll(PsoPersistentVolumeClaim::PREFIX);
        PsoPod::deleteAll(PsoPod::PREFIX);
        PsoJob::deleteAll(PsoJob::PREFIX);
        PsoNode::deleteAll(PsoNode::PREFIX);
        PsoStatefulSet::DeleteAll(PsoStatefulSet::PREFIX);
        PsoStorageClass::deleteAll(PsoStorageClass::PREFIX);
        PsoVolumeSnapshotClass::deleteAll(PsoVolumeSnapshotClass::PREFIX);
        PsoVolumeSnapshot::deleteAll(PsoVolumeSnapshot::PREFIX);


        // Get PSO namespace and prefix from Kubernetes
        if (!$this->getPsoDetails()) {
            Redis::del(self::PSO_UPDATE_KEY);
            return false;
        }

        // Get FlashArray™ and FlashBlade®
        if (!$this->getArrayInfo()) {
            Redis::del(self::PSO_UPDATE_KEY);
            return false;
        }

        // Get the storageclasses that use PSO
        if (!$this->getStorageClasses()) {
            Redis::del(self::PSO_UPDATE_KEY);
            return false;
        }

        // Get the persistent volume claims
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

        // Get the nodes
        if (!$this->getNodes()) {
            Redis::del(self::PSO_UPDATE_KEY);
            return false;
        }

        // Get the VolumeSnapshotClasses
        $this->getVolumeSnapshotClasses();

        // Get the VolumeSnapshots
        $this->getVolumeSnapshots();

        // Get Pure Storage® array information
        $this->addArrayVolumeInfo();

        // Check for released PV's
        if (!$this->getPersistentVolumes()) {
            return false;
        }

        Redis::set(self::VALID_PSO_DATA_KEY, time());
        Redis::expire(self::VALID_PSO_DATA_KEY, $this->refreshTimeout);

        $this->errorSource = '';
        $this->errorMessage = '';
        Redis::del(self::PSO_UPDATE_KEY, time());
        Log::debug('    Refresh data completed.');
        Log::debug('');
        return true;
    }

    public function dashboard()
    {
        $this->RefreshData();

        $dashboard = null;

        $dashboard['volume_count'] = count(PsoPersistentVolumeClaim::items(PsoPersistentVolumeClaim::PREFIX, 'uid'));
        $dashboard['orphaned_count'] = count(
            PsoPersistentVolumeClaim::items(
                PsoPersistentVolumeClaim::PREFIX,
                'pure_orphaned'
            )
        );
        $dashboard['storageclass_count'] = count(PsoStorageClass::items(PsoStorageClass::PREFIX, 'name'));
        $dashboard['snapshot_count'] = count(PsoVolumeSnapshot::items(PsoVolumeSnapshot::PREFIX, 'uid'));
        $dashboard['array_count'] = count(PsoArray::items(PsoArray::PREFIX, 'name'));
        $dashboard['offline_array_count'] = count(PsoArray::items(PsoArray::PREFIX, 'offline'));

        $vols = [];
        foreach (PsoPersistentVolumeClaim::items(PsoPersistentVolumeClaim::PREFIX, 'uid') as $uid) {
            $volume = new PsoPersistentVolumeClaim($uid);

            if (($volume->pure_orphaned == null) and ($volume->pure_arrayType == 'FA')) {
                $vol['uid'] = $uid;
                $vol['name'] = $volume->name;
                $vol['pure_name'] = $volume->pure_name;
                $vol['size'] = $volume->pure_size;
                $vol['sizeFormatted'] = $volume->pure_sizeFormatted;
                $vol['used'] = $volume->pure_used;
                $vol['usedFormatted'] = $volume->pure_usedFormatted;
                $vol['growth'] = $volume->pure_used - $volume->pure_24h_historic_used;
                $vol['growthFormatted'] = $this->formatBytes($volume->pure_used -
                    $volume->pure_24h_historic_used, 2);
                $vol['status'] = $volume->status;

                if ($volume->pure_size !== null) {
                    $vol['growthPercentage'] = ($volume->pure_used - $volume->pure_24h_historic_used) /
                        $volume->pure_size * 100;
                } else {
                    $vol['growthPercentage'] = 0;
                }
                array_push($vols, $vol);
            }
        }

        $uids = array_column($vols, 'uid');
        $growths = array_column($vols, 'growthPercentage');

        array_multisort($growths, SORT_DESC, $uids, SORT_DESC, $vols);

        $dashboard['top10_growth_vols'] = array_slice($vols, 0, 10);

        return $dashboard;
    }

    public function volumes()
    {
        $this->RefreshData();

        $volumes = [];
        foreach (PsoPersistentVolumeClaim::items(PsoPersistentVolumeClaim::PREFIX, 'uid') as $uid) {
            $volume = new PsoPersistentVolumeClaim($uid);
            if ($volume->pure_orphaned == null) {
                array_push($volumes, $volume->asArray());
            }
        }
        return $volumes;
    }

    public function orphaned()
    {
        $this->RefreshData();

        $volumes = [];
        foreach (PsoPersistentVolumeClaim::items(PsoPersistentVolumeClaim::PREFIX, 'pure_orphaned') as $uid) {
            $volume = new PsoPersistentVolumeClaim($uid);
            array_push($volumes, $volume->asArray());
        }
        return $volumes;
    }

    public function arrays()
    {
        $this->RefreshData();

        $pso_arrays = [];

        foreach (PsoArray::items(PsoArray::PREFIX, 'mgmtEndPoint') as $item) {
            $myarray = new PsoArray($item);

            if (($myarray->size == null) or ($myarray->used == null) or ($myarray->volumeCount == null)) {
                $size = 0;
                $used = 0;
                $volumeCount = 0;
                $storageClasses = [];

                foreach (PsoPersistentVolumeClaim::items(PsoPersistentVolumeClaim::PREFIX, 'uid') as $uid) {
                    $myvol = new PsoPersistentVolumeClaim($uid);

                    if ($myvol->pure_arrayMgmtEndPoint == $item) {
                        $size = $size + $myvol->pure_size;
                        $used = $used + $myvol->pure_used;
                        $volumeCount = $volumeCount + 1;
                        if (!in_array($myvol->storageClass, $storageClasses) and ($myvol->storageClass !== null)) {
                            array_push($storageClasses, $myvol->storageClass);
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
            array_push($pso_arrays, $myarray->asArray());
        }

        return $pso_arrays;
    }

    public function namespaces()
    {
        $this->RefreshData();

        $namespaces = [];
        $pure_storageclasses = PsoStorageClass::items(PsoStorageClass::PREFIX, 'name');

        foreach (PsoPersistentVolumeClaim::items(PsoPersistentVolumeClaim::PREFIX, 'namespace') as $item) {
            $pure_size = 0;
            $pure_used = 0;
            $pure_volumes = 0;
            $storageclasses = [];

            foreach (PsoPersistentVolumeClaim::items(PsoPersistentVolumeClaim::PREFIX, 'uid') as $uid) {
                $myvol = new PsoPersistentVolumeClaim($uid);

                if (($myvol->namespace == $item) and in_array($myvol->storageClass, $pure_storageclasses)) {
                    $pure_size = $pure_size + $myvol->pure_size;
                    $pure_used = $pure_used + $myvol->pure_used;
                    $pure_volumes = $pure_volumes + 1;
                    if (!in_array($myvol->storageClass, $storageclasses)) {
                        array_push($storageclasses, $myvol->storageClass);
                    }
                }
            }

            $namespace_info = new PsoNamespace($item);
            $namespace_info->size = $pure_size;
            $namespace_info->sizeFormatted = $this->formatBytes($pure_size, 2);
            $namespace_info->used = $pure_used;
            $namespace_info->usedFormatted = $this->formatBytes($pure_used, 2);
            $namespace_info->volumeCount = $pure_volumes;
            $namespace_info->storageClasses = implode(', ', $storageclasses);

            array_push($namespaces, $namespace_info->asArray());
        }
        return $namespaces;
    }

    public function storageclasses()
    {
        $this->RefreshData();

        $storageclasses = [];

        foreach (PsoStorageClass::items(PsoStorageClass::PREFIX, 'name') as $storageclass) {
            $pure_size = 0;
            $pure_used = 0;
            $pure_volumes = 0;

            foreach (PsoPersistentVolumeClaim::items(PsoPersistentVolumeClaim::PREFIX, 'uid') as $uid) {
                $myvol = new PsoPersistentVolumeClaim($uid);

                if ($myvol->storageClass == $storageclass) {
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

    public function volumesnapshotclasses()
    {
        $this->RefreshData();

        $volumesnapshotclasses = [];

        foreach (PsoVolumeSnapshotClass::items(PsoVolumeSnapshotClass::PREFIX, 'name') as $volumesnapshotclass) {
            $pure_size = 0;
            $pure_used = 0;
            $pure_volumes = 0;

            foreach (PsoVolumeSnapshot::items(PsoVolumeSnapshot::PREFIX, 'uid') as $uid) {
                $mysnap = new PsoVolumeSnapshot($uid);

                if ($mysnap->snapshotClassName == $volumesnapshotclass) {
                    $pure_size = $pure_size + $mysnap->pure_size;
                    $pure_used = $pure_used + $mysnap->pure_used;
                    $pure_volumes = $pure_volumes + 1;
                }
            }

            $volumesnapshotclass_info = new PsoVolumeSnapshotClass($volumesnapshotclass);
            $volumesnapshotclass_info->size = $pure_size;
            $volumesnapshotclass_info->sizeFormatted = $this->formatBytes($pure_size, 2);
            $volumesnapshotclass_info->used = $pure_used;
            $volumesnapshotclass_info->usedFormatted = $this->formatBytes($pure_used, 2);
            $volumesnapshotclass_info->volumeCount = $pure_volumes;

            array_push($volumesnapshotclasses, $volumesnapshotclass_info->asArray());
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

    public function labels()
    {
        $this->RefreshData();

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
                    if (in_array($label, $myvol->labels) and in_array($myvol->storageClass, $pure_storageclasses)) {
                        $pure_size = $pure_size + $myvol->pure_size;
                        $pure_used = $pure_used + $myvol->pure_used;
                        $pure_volumes = $pure_volumes + 1;
                        if (!in_array($myvol->storageClass, $storageclasses)) {
                            array_push($storageclasses, $myvol->storageClass);
                        }
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
                $label_info->storageClasses = implode(', ', $storageclasses);

                array_push($labels, $label_info->asArray());
            }
        }

        return $labels;
    }

    public function pods()
    {
        $this->RefreshData();

        $pods = [];
        $pvc_list = PsoPersistentVolumeClaim::items(PsoPersistentVolumeClaim::PREFIX, 'namespace_name');

        foreach (PsoPod::items(PsoPod::PREFIX, 'uid') as $uid) {
            $pod = new PsoPod($uid);

            $pvcs = [];
            $pvc_links = [];
            $pure_size = 0;
            $pure_used = 0;
            $volumeCount = 0;
            $storageClasses = [];
            $storageClasses = [];

            foreach (($pod->pvc_namespace_name ?? []) as $item) {
                if (in_array($item, $pvc_list)) {
                    $namespace = explode(':', $item)[0];
                    $name = explode(':', $item)[1];

                    $uid = PsoPersistentVolumeClaim::getUidByNamespaceName($namespace, $name);
                    $my_pvc = new PsoPersistentVolumeClaim($uid);

                    array_push($pvcs, $item);
                    $volumeCount = $volumeCount + 1;
                    $pure_size = $pure_size + $my_pvc->pure_size;
                    $pure_used = $pure_used + $my_pvc->pure_used;
                    if (!in_array($my_pvc->storageClass, $storageClasses)) {
                        array_push($storageClasses, $my_pvc->storageClass);
                    }

                    array_push(
                        $pvc_links,
                        '<a href="' . route(
                            'Storage-Volumes',
                            ['volume_keyword' => $my_pvc->uid]
                        ) . '">' . $my_pvc->name . '</a>'
                    );
                }
            }

            $pod->size = $pure_size;
            $pod->sizeFormatted = $this->formatBytes($pure_size, 2);
            $pod->used = $pure_used;
            $pod->usedFormatted = $this->formatBytes($pure_used, 2);
            $pod->volumeCount = $volumeCount;
            $pod->storageClasses = $storageClasses;
            $pod->pvc_link = $pvc_links;

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
            $pvc_links = [];
            $pure_size = 0;
            $pure_used = 0;
            $volumeCount = 0;
            $storageClasses = [];
            $storageClasses = [];

            foreach (($job->pvc_namespace_name ?? []) as $item) {
                $namespace = explode(':', $item)[0];
                $name = explode(':', $item)[1];

                $uid = PsoPersistentVolumeClaim::getUidByNamespaceName($namespace, $name);
                $my_pvc = new PsoPersistentVolumeClaim($uid);

                array_push($pvcs, $item);
                $volumeCount = $volumeCount + 1;
                $pure_size = $pure_size + $my_pvc->pure_size;
                $pure_used = $pure_used + $my_pvc->pure_used;
                if (!in_array($my_pvc->storageClass, $storageClasses)) {
                    array_push($storageClasses, $my_pvc->storageClass);
                }

                array_push(
                    $pvc_links,
                    '<a href="' . route(
                        'Storage-Volumes',
                        ['volume_keyword' => $my_pvc->uid]
                    ) . '">' . $my_pvc->name . '</a>'
                );
            }

            $job->size = $pure_size;
            $job->sizeFormatted = $this->formatBytes($pure_size, 2);
            $job->used = $pure_used;
            $job->usedFormatted = $this->formatBytes($pure_used, 2);
            $job->volumeCount = $volumeCount;
            $job->storageClasses = $storageClasses;
            $job->pvc_link = $pvc_links;

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

        foreach (PsoDeployment::items(PsoDeployment::PREFIX, 'uid') as $deployment_uid) {
            $deployment = new PsoDeployment($deployment_uid);

            $pure_size = 0;
            $pure_used = 0;
            $pure_volumes = 0;
            $storageclasses = [];

            foreach (PsoPersistentVolumeClaim::items(PsoPersistentVolumeClaim::PREFIX, 'uid') as $uid) {
                $myvol = new PsoPersistentVolumeClaim($uid);

                if ((in_array($myvol->namespace . ':' . $myvol->name, $deployment->namespace_names))) {
                    $pure_size = $pure_size + $myvol->pure_size;
                    $pure_used = $pure_used + $myvol->pure_used;
                    $pure_volumes = $pure_volumes + 1;
                    if (!in_array($myvol->storageClass, $storageclasses)) {
                        array_push($storageclasses, $myvol->storageClass);
                    }
                }
            }

            $deployment->size = $pure_size;
            $deployment->sizeFormatted = $this->formatBytes($pure_size, 2);
            $deployment->used = $pure_used;
            $deployment->usedFormatted = $this->formatBytes($pure_used, 2);
            $deployment->volumeCount = $pure_volumes;
            $deployment->storageClasses = implode(', ', $storageclasses);

            array_push($deployments, $deployment->asArray());
        }

        return $deployments;
    }

    public function statefulsets()
    {
        $this->RefreshData();

        $statefulsets = [];
        foreach (PsoStatefulSet::items(PsoStatefulSet::PREFIX, 'uid') as $statefulset_uid) {
            $myset = new PsoStatefulSet($statefulset_uid);

            $pure_size = 0;
            $pure_used = 0;
            $pure_volumes = 0;
            $storageclasses = [];

            foreach (PsoPersistentVolumeClaim::items(PsoPersistentVolumeClaim::PREFIX, 'uid') as $uid) {
                $myvol = new PsoPersistentVolumeClaim($uid);

                if ((in_array($myvol->namespace . ':' . $myvol->name, $myset->namespace_names))) {
                    $pure_size = $pure_size + $myvol->pure_size;
                    $pure_used = $pure_used + $myvol->pure_used;
                    $pure_volumes = $pure_volumes + 1;
                    if (!in_array($myvol->storageClass, $storageclasses)) {
                        array_push($storageclasses, $myvol->storageClass);
                    }
                }
            }

            $myset->size = $pure_size;
            $myset->sizeFormatted = $this->formatBytes($pure_size, 2);
            $myset->used = $pure_used;
            $myset->usedFormatted = $this->formatBytes($pure_used, 2);
            $myset->volumeCount = $pure_volumes;
            $myset->storageClasses = implode(', ', $storageclasses);

            array_push($statefulsets, $myset->asArray());
        }

        return $statefulsets;
    }

    public function portalInfo()
    {
        $portalInfo['total_used'] = $this->formatBytes($this->psoInfo->totalused);
        $portalInfo['total_size'] = $this->formatBytes($this->psoInfo->totalsize);
        $portalInfo['total_used_raw'] = $this->psoInfo->totalused;
        $portalInfo['total_size_raw'] = $this->psoInfo->totalsize;
        $portalInfo['total_orphaned_raw'] = $this->psoInfo->total_orphaned_used;
        $portalInfo['total_snapshot_raw'] = $this->psoInfo->total_snapshot_used;
        $portalInfo['last_refesh'] = Redis::get(self::VALID_PSO_DATA_KEY);

        $portalInfo['total_iops_read'] = $this->psoInfo->total_iops_read;
        $portalInfo['total_iops_write'] = $this->psoInfo->total_iops_write;
        $portalInfo['total_bw'] = $this->formatBytes(
            $this->psoInfo->total_bw_read + $this->psoInfo->total_bw_write,
            1,
            2);
        $portalInfo['total_bw_read'] = $this->formatBytes($this->psoInfo->total_bw_read, 1, 2);
        $portalInfo['total_bw_write'] = $this->formatBytes($this->psoInfo->total_bw_write, 1, 2);

        $portalInfo['low_msec_read'] = round($this->psoInfo->low_msec_read, 2);
        $portalInfo['low_msec_write'] = round($this->psoInfo->low_msec_write, 2);
        $portalInfo['high_msec_read'] = round($this->psoInfo->high_msec_read, 2);
        $portalInfo['high_msec_write'] = round($this->psoInfo->high_msec_write, 2);

        return $portalInfo;
    }

    public function settings()
    {
        $this->RefreshData();

        return $this->psoInfo->asArray();
    }

    public function log()
    {
        Client::configure($this->master, $this->authentication, ['timeout' => 10]);
        $podLog = new PodLog();

        return $podLog->readLog(
            $this->psoInfo->namespace,
            $this->psoInfo->provisioner_pod,
            ['container' => $this->psoInfo->provisioner_container,
                'tailLines' => '1000']
        );
    }
}
