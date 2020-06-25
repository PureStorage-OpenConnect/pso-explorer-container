<?php


namespace App\Http\Classes;


class PsoVolumeSnapshotClass extends RedisModel
{
    public const PREFIX='pso_snapshotclass';

    protected $fillable = [
        'name',
        'size',
        'sizeFormatted',
        'used',
        'usedFormatted',
        'volumeCount',

        'snapshotter',
        'reclaimPolicy',
        'is_default_class',
    ];

    protected $indexes = [
        'name',
    ];


    public function __construct(string $name)
    {
        parent::__construct(SELF::PREFIX, $name);

        if ($name !== '') $this->name = $name;
    }
}
