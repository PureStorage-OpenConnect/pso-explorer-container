<?php

namespace App\Http\Classes;

class PsoInformation extends RedisModel
{
    public const PREFIX = 'psoInformation';

    protected $fillable = [
        'prefix',
        'namespace',
        'images',
        'helmChart',
        'provisionerPod',
        'provisionerLabels',
        'provisionerContainer',
        'provisionerImage',
        'provisionerTag',
        'isCsiDriver',
        'isOpenShift',
        'psoNodes',
        'psoProvisionerNode',
        'dbMaxSuspectSeconds',
        'dbMaxStartupSeconds',
        'faPreemptAttachments',
        'repoUri',
        'psoEdition',
        'valuesUri',
        'sanType',
        'faDefaultFsType',
        'enableFbNfsSnapshot',
        'nfsExportRules',
        'faDefaultFSOpt',
        'faDefaultMountOpt',
        'faIscsiLoginTimeout',
        'faIscsiAllowedCidr',
        'totalUsed',
        'totalOrphanedUsed',
        'totalSnapshotUsed',
        'totalSize',
        'yaml',
        'psoStorageTopology',
        'psoStrictTopology',
        'snapshotApiVersion',
        'totalIopsRead',
        'totalIopsWrite',
        'totalBwRead',
        'totalBwWrite',
        'lowMsecRead',
        'lowMsecWrite',
        'highMsecRead',
        'highMsecWrite',
    ];

    public function __construct()
    {
        $prefix = self::PREFIX;
        $uid = 'global';
        parent::__construct($prefix, $uid);
    }
}
