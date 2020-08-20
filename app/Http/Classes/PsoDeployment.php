<?php

namespace App\Http\Classes;

use Illuminate\Support\Facades\Redis;

class PsoDeployment extends RedisModel
{
    public const PREFIX = 'pso_deployment';

    protected $fillable = [
        'uid',
        'name',
        'namespace',
        'namespaceNames',
        'creationTimestamp',
        'replicas',
        'labels',
        'size',
        'sizeFormatted',
        'used',
        'usedFormatted',
        'volumeCount',
        'storageClasses',
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
}
