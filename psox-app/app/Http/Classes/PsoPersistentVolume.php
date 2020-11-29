<?php

namespace App\Http\Classes;

use Illuminate\Support\Facades\Redis;

class PsoPersistentVolume extends RedisModel
{
    public const PREFIX = 'pso_pv';

    protected $fillable = [
        // Primary index
        'name',

        // metadata fields
        'creationTimestamp',
        'updateTimestamp',
        'finalizers',
        'resourceVersion',
        'uid',

        // spec fields
        'accessModes',
        'capacity',
        'labels',
        'persistentVolumeReclaimPolicy',
        'storageClassName',
        'volumeMode',

        // spec->csi fields
        'csi_backend',
        'csi_createoptions',
        'csi_driver',
        'csi_fsType',
        'csi_namespace',
        'csi_volumeHandle',
        'csi_volumeName',

        // spec->status fields
        'status_message',
        'status_phase',
        'status_reason',

        // spec->claimRef fields
        'claimRef_name',
        'claimRef_namespace',
        'claimRef_resourceVersion',
        'claimRef_uid',

        // Pure Storage volume fields
        'pureName',
        'pureSize',
        'pureSizeFormatted',
        'pureTotal',
        'pureUsed',
        'pureUsedFormatted',
        'pureDrr',
        'pureThinProvisioning',
        'pureArrayName',
        'pureArrayType',
        'pureArrayMgmtEndPoint',
        'pureSnapshots',
        'pureVolumes',
        'pureSharedSpace',
        'pureTotalReduction',

        // Pure Storage volume performance fields
        'pureReadsPerSec',
        'pureWritesPerSec',
        'pureInputPerSec',
        'pureInputPerSecFormatted',
        'pureOutputPerSec',
        'pureOutputPerSecFormatted',
        'pureUsecPerReadOp',
        'pureUsecPerWriteOp',
        'pure24hHistoricTotal',

        // Calculated data fields
        'isReleased',
        'isOrphaned',
    ];

    protected $indexes = [
        'uid',
        'name',
        'isReleased',
        'isOrphaned',
    ];


    public function __construct(string $name)
    {
        parent::__construct(self::PREFIX, $name);
        if ($name !== '') {
            $this->name = $name;
        }
    }

    public static function getNameBycsiVolumeHandle(string $volumeHandle)
    {
        $keys = Redis::keys(self::PREFIX . ':*:csi_volumeHandle');
        foreach ($keys as $key) {
            if (!strpos($key, '__index')) {
                $keyname = str_replace(config('database.redis.options.prefix'), '', $key);

                if (Redis::get($keyname) == $volumeHandle) {
                    $keyname = str_replace(':csi_volumeHandle', ':name', $keyname);
                    $name = Redis::get($keyname);
                    return $name;
                }
            }
        }
    }
}
