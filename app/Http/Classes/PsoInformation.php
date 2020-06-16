<?php


namespace App\Http\Classes;


class PsoInformation extends RedisModel
{
    public const PREFIX='pso_information';

    protected $fillable = [
        'prefix', 'namespace', 'totalused', 'totalsize', 'yaml', 'total_iops_read', 'total_iops_write', 'total_bw_read', 'total_bw_write', 'low_msec_read', 'low_msec_write', 'high_msec_read', 'high_msec_write',
    ];

    public function __construct()
    {
        $prefix = SELF::PREFIX;
        $uid = 'global';
        parent::__construct($prefix, $uid);
    }
}