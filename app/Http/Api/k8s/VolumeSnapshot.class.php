<?php

namespace App\Api\k8s;

class VolumeSnapshot extends \KubernetesRuntime\AbstractAPI
{

    /**
     * list or watch objects of kind VolumeSnapshot
     *
     * @configkey allowWatchBookmarks	boolean
     * @configkey continue	string
     * @configkey fieldSelector	string
     * @configkey labelSelector	string
     * @configkey limit	integer
     * @configkey resourceVersion	string
     * @configkey timeoutSeconds	integer
     * @configkey watch	boolean
     * @configkey allowWatchBookmarks	boolean
     * @configkey continue	string
     * @configkey fieldSelector	string
     * @configkey labelSelector	string
     * @configkey limit	integer
     * @configkey resourceVersion	string
     * @configkey timeoutSeconds	integer
     * @configkey watch	boolean
     * @param $namespace
     * @param array $queries
     * @return VolumeSnapshotList|mixed
     */
    public function listV1alpha1($namespace = 'default', array $queries = [])
    {
        return $this->parseResponse(
        	$this->client->request('get',
        		"/apis/snapshot.storage.k8s.io/v1alpha1/namespaces/{$namespace}/volumesnapshots"
        		,[
        			'query' => $queries,
        		]
        	)
        	, 'listCoreV1NamespacedVolumeSnapshot'
        );
    }
}

