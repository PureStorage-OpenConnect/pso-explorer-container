<?php

namespace App\Http\Classes;

use Illuminate\Support\Facades\Redis;

class PsoArray extends RedisModel
{
    public const PREFIX = 'pso_array';

    protected $fillable = [
        'uid',
        'name',
        'mgmtEndPoint',
        'apiToken',
        'model',
        'version',
        'labels',
        'message',
        'size',
        'sizeFormatted',
        'used',
        'usedFormatted',
        'volumeCount',
        'storageClasses',
        'offline',
        'flasharray',
        'flashblade',
        'protocols',
        'iSCSIEndpoints',
        'nfsEndpoints'
    ];

    protected $indexes = [
        'name',
        'mgmtEndPoint',
        'offline',
    ];


    public function __construct(string $mgmtEndPoint)
    {
        parent::__construct(self::PREFIX, $mgmtEndPoint);

        if ($mgmtEndPoint !== '') {
            $this->mgmtEndPoint = $mgmtEndPoint;
        }
    }
}
