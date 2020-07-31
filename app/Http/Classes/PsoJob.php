<?php

namespace App\Http\Classes;

class PsoJob extends RedisModel
{
    public const PREFIX = 'pso_job';

    protected $fillable = [
        'uid',
        'name',
        'namespace',
        'creationTimestamp',
        'pvc_name',
        'pvc_namespace_name',
        'pvc_link',
        'labels',
        'status',
        'size',
        'sizeFormatted',
        'used',
        'usedFormatted',
        'volumeCount',
        'storageClasses',
    ];

    protected $indexes = [
        'uid',
        'pvc_namespace_name',
    ];


    public function __construct(string $uid)
    {
        parent::__construct(self::PREFIX, $uid);

        if ($uid !== '') {
            $this->uid = $uid;
        }
    }
}
