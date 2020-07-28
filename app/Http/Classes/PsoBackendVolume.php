<?php


namespace App\Http\Classes;

use Illuminate\Support\Facades\Redis;

class PsoBackendVolume extends RedisModel
{
    public const PREFIX='pso_dbvol';

    protected $fillable = [
        'pure_arrayName_volName',
        'unhealthy',
        'pure_name',
        'pure_size',
        'pure_sizeFormatted',
        'pure_used',
        'pure_usedFormatted',
        'pure_drr',
        'pure_thinProvisioning',
        'pure_arrayName',
        'pure_arrayType',
        'pure_arrayMgmtEndPoint',
        'pure_sharedSpace',
        'pure_totalReduction',
    ];

    protected $indexes = [
        'pure_name',
        'pure_arrayName_volName',
    ];


    public function __construct(string $pure_arrayName_volName)
    {
        parent::__construct(SELF::PREFIX, $pure_arrayName_volName);
        if ($pure_arrayName_volName !== '') $this->pure_arrayName_volName = $pure_arrayName_volName;
    }
}
