<?php

namespace App\Http\Classes;

class PsoLabels extends RedisModel
{
    public const PREFIX = 'pso_labels';

    protected $fillable = [
        'label',
        'key',
        'value',
        'size',
        'sizeFormatted',
        'used',
        'usedFormatted',
        'volumeCount',
        'storageClasses',
    ];

    protected $indexes = [
        'label',
    ];


    public function __construct(string $label)
    {
        parent::__construct(self::PREFIX, $label);

        if ($label !== '') {
            $this->label = $label;
        }
    }
}
