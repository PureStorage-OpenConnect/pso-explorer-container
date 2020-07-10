<?php


namespace App\Http\Classes;


class PsoInformation extends RedisModel
{
    public const PREFIX='pso_information';

    protected $fillable = [
        'prefix',
        'namespace',
        'images',
        'provisioner_pod',
        'provisioner_container',
        'san_type',
        'block_fs_type',
        'block_fs_opt',
        'block_mnt_opt',
        'iscsi_login_timeout',
        'iscsi_allowed_cidrs',
        'totalused',
        'total_orphaned_used',
        'total_snapshot_used',
        'totalsize',
        'yaml',
        'snapshot_api_version',
        'total_iops_read',
        'total_iops_write',
        'total_bw_read',
        'total_bw_write',
        'low_msec_read',
        'low_msec_write',
        'high_msec_read',
        'high_msec_write',
    ];

    public function __construct()
    {
        $prefix = SELF::PREFIX;
        $uid = 'global';
        parent::__construct($prefix, $uid);
    }
}
