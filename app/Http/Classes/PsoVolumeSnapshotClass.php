<?php

namespace App\Http\Classes;

class PsoVolumeSnapshotClass extends RedisModel
{
    public const PREFIX = 'pso_snapshotclass';

    protected $fillable = [
        'name',
        'size',
        'sizeFormatted',
        'used',
        'usedFormatted',
        'volumeCount',

        'snapshotter',
        'reclaimPolicy',
        'isDefaultClass',
    ];

    protected $indexes = [
        'name',
    ];


    public function __construct(string $name)
    {
        parent::__construct(self::PREFIX, $name);

        if ($name !== '') {
            $this->name = $name;
        }
    }
}
