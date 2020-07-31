<?php

namespace App\Http\Classes;

use Illuminate\Support\Facades\Redis;

class PsoPersistentVolumeClaim extends RedisModel
{
    public const PREFIX = 'pso_pvc';

    protected $fillable = [
        'uid',
        'name',
        'namespace',
        'namespaceName',
        'size',
        'storageClass',
        'labels',
        'status',
        'creationTimestamp',

        'pvName',
        'hasSnaps',

        'pureName',
        'pureSize',
        'pureSizeFormatted',
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
        'pureOrphaned',
        'pureOrphanedState',
        'pureOrphanedPvcName',
        'pureOrphanedPvcNamespace',

        'pureReadsPerSec',
        'pureWritesPerSec',
        'pureInputPerSec',
        'pureInputPerSecFormatted',
        'pureOutputPerSec',
        'pureOutputPerSecFormatted',
        'pureUsecPerReadOp',
        'pureUsecPerWriteOp',
        'pure24hHistoricUsed',
    ];

    protected $indexes = [
        'uid',
        'namespace',
        'namespaceName',
        'pureOrphaned',
        'labels',
    ];


    public function __construct(string $uid)
    {
        parent::__construct(self::PREFIX, $uid);
        if ($uid !== '') {
            $this->uid = $uid;
        }
    }

    public static function getUidByNamespaceName(string $namespace, string $name)
    {
        $namespaceName = $namespace . ':' . $name;

        $keys = Redis::keys(self::PREFIX . ':*:namespaceName');
        foreach ($keys as $key) {
            if (!strpos($key, '__index')) {
                $keyname = str_replace(config('database.redis.options.prefix'), '', $key);

                if (Redis::get($keyname) == $namespaceName) {
                    $keyname = str_replace(':namespaceName', ':uid', $keyname);
                    $uid = Redis::get($keyname);
                    return $uid;
                }
            }
        }
    }
}
