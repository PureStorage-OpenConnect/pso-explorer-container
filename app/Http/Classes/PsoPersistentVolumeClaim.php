<?php

namespace App\Http\Classes;

use Illuminate\Support\Facades\Redis;

class PsoPersistentVolumeClaim extends RedisModel
{
    public const PREFIX = 'pso_pvc';
    public $pv = null;

    protected $fillable = [
        // Primary index
        'uid',

        // metadata fields
        'annotations',
        'creationTimestamp',
        'finalizers',
        'labels',
        'name',
        'namespace',
        'resourceVersion',

        // spec fields
        'accessModes',
        'storageClassName',
        'volumeMode',
        'volumeName',

        // spec->status fields
        'status_accessModes',
        'status_capacity',
        'status_conditions',
        'status_phase',

        // Calculated data fields
        'namespaceName',
        'hasSnaps',
    ];

    protected $indexes = [
        'uid',
        'labels',
        'namespace',
        'namespaceName',
        'pureOrphaned',
        'volumeName',
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

    public function asArray()
    {
        $array = parent::asArray();
        if ($this->volumeName !== null) {
            $pv = new PsoPersistentVolume($this->volumeName);
            $array['pv'] = $pv->asArray();
        }

        return $array;
    }
}
