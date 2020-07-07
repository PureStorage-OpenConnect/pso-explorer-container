<?php

namespace App\Api\k8s;

use \Kubernetes\Model\Io\K8s\Api\Core\V1\Pod as ThePod;

class PodLog extends \KubernetesRuntime\AbstractAPI
{

    /**
     * read log of the specified Pod
     *
     * @param $namespace
     * @param $name
     * @return string|mixed
     */
    public function readLog($namespace = 'default', $name, array $queries = [])
    {
        return $this->parseResponse(
        	$this->client->request('get',
        		"/api/v1/namespaces/{$namespace}/pods/{$name}/log"
        		,[
                    'query' => $queries,
        		]
        	)
        	, 'readCoreV1NamespacedPodLog'
        );
    }
}

