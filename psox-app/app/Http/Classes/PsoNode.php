<?php

namespace App\Http\Classes;

use Illuminate\Support\Facades\Redis;

class PsoNode extends RedisModel
{
    public const PREFIX = 'pso_node';

    protected $fillable = [
        'uid',
        'name',
        'labels',
        'creationTimestamp',
        'podCIDR',
        'podCIDRs',
        'taints',
        'unschedulable',
        'architecture',
        'containerRuntimeVersion',
        'kernelVersion',
        'kubeletVersion',
        'osImage',
        'operatingSystem',
        'hostname',
        'internalIP',
        'conditions',
        'conditionMessages',
        'pingStatus',
        'pingErrors',
    ];

    protected $indexes = [
        'uid',
        'name',
    ];


    public function __construct(string $uid)
    {
        parent::__construct(self::PREFIX, $uid);

        if ($uid !== '') {
            $this->uid = $uid;
        }
    }

    public static function getUidByNodeName(string $name)
    {
        $keys = Redis::keys(self::PREFIX . ':*:name');
        foreach ($keys as $key) {
            if (!strpos($key, '__index')) {
                $keyname = str_replace(config('database.redis.options.prefix'), '', $key);

                if (Redis::get($keyname) == $name) {
                    $keyname = str_replace(':name', ':uid', $keyname);
                    $uid = Redis::get($keyname);
                    return $uid;
                }
            }
        }
    }
}
