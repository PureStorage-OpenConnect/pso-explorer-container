<?php

namespace App\Http\Classes;

class PsoVolumeSnapshot extends RedisModel
{
    public const PREFIX = 'pso_snapshot';

    protected $fillable = [
        'uid',
        'name',
        'namespace',
        'creationTimestamp',

        'snapshotClassName',
        'snapshotContentName',
        'sourceName',
        'sourceKind',
        'creationTime',
        'readyToUse',
        'errorMessage',
        'errorTime',
        'orphaned',

        'pureName',
        'pureVolName',
        'pureSize',
        'pureSizeFormatted',
        'pureUsed',
        'pureUsedFormatted',
        'pureArrayName',
        'pureArrayType',
        'pureArrayMgmtEndPoint',
    ];

    protected $indexes = [
        'uid',
        'name',
        'orphaned',
    ];


    public function __construct(string $uid)
    {
        parent::__construct(self::PREFIX, $uid);

        if ($uid !== '') {
            $this->uid = $uid;
        }
    }
}
