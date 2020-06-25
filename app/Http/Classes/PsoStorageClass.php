<?php


namespace App\Http\Classes;


class PsoStorageClass extends RedisModel
{
    public const PREFIX='pso_storageclass';

    protected $fillable = [
        'name',
        'size',
        'sizeFormatted',
        'used',
        'usedFormatted',
        'volumeCount',
        'mountOptions',
        'parameters',
        'allowVolumeExpansion',
        'volumeBindingMode',
        'reclaimPolicy',
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
