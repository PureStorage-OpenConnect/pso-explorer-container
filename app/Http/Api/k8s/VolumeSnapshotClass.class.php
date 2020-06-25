<?php

namespace App\Api\k8s;

class VolumeSnapshotClass extends \KubernetesRuntime\AbstractAPI
{

    /**
     * list or watch objects of kind VolumeSnapshotClass
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
     * @param array $queries
     * @return VolumeSnapshotClassList|mixed
     */
    public function listV1alpha1(array $queries = [])
    {
        return $this->parseResponse(
            $this->client->request('get',
                "/apis/snapshot.storage.k8s.io/v1alpha1/volumesnapshotclasses"
                ,[
                    'query' => $queries,
                ]
            )
            , 'listStorageV1alpha1VolumeSnapshotClass'
        );
    }
}