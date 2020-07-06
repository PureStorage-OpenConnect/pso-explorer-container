<?php


namespace App\Http\Classes;


class PsoVolumeSnapshot extends RedisModel
{
    public const PREFIX='pso_snapshot';

    protected $fillable = [
        'uid',
        'name',
        'namespace',
        'creationTimestamp',

        'snapshotClassName',
        'snapshotContentName',
        'source_name',
        'source_kind',
        'creationTime',
        'readyToUse',
        'error_message',
        'error_time',

        'pure_name',
        'pure_volname',
        'pure_size',
        'pure_sizeFormatted',
        'pure_used',
        'pure_usedFormatted',
        'pure_arrayName',
        'pure_arrayType',
        'pure_arrayMgmtEndPoint',
    ];

    protected $indexes = [
        'uid',
        'name',
    ];


    public function __construct(string $uid)
    {
        parent::__construct(SELF::PREFIX, $uid);

        if ($uid !== '') $this->uid = $uid;
    }
}
