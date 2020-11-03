<?php

namespace App\Http\Classes;

use Illuminate\Support\Facades\Redis;

class PsoBackendVolume extends RedisModel
{
    public const PREFIX = 'pso_dbvol';

    protected $fillable = [
        'pureArrayNameVolName',
        'unhealthy',
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
        'pureSharedSpace',
        'pureTotalReduction',
    ];

    protected $indexes = [
        'pureArrayNameVolName',
    ];


    public function __construct(string $pureArrayNameVolName)
    {
        parent::__construct(self::PREFIX, $pureArrayNameVolName);
        if ($pureArrayNameVolName !== '') {
            $this->pureArrayNameVolName = $pureArrayNameVolName;
        }
    }
}
