<?php

namespace App\Http\Classes;

class PsoNamespace extends RedisModel
{
    public const PREFIX = 'pso_namespace';

    protected $fillable = [
        'namespace',
        'size',
        'sizeFormatted',
        'used',
        'usedFormatted',
        'volumeCount',
        'storageClasses',
    ];

    protected $indexes = [
        'namespace',
    ];


    public function __construct(string $namespace)
    {
        parent::__construct(self::PREFIX, $namespace);

        if ($namespace !== '') {
            $this->namespace = $namespace;
        }
    }
}
