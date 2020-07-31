<?php

namespace App\Http\Classes;

class PsoNode extends RedisModel
{
    public const PREFIX = 'pso_node';

    protected $fillable = [
        'uid',
        'name',
        'labels',
        'creationTimestamp',
        'podCIDR',
        'podCIDRs',
        'taints',
        'unschedulable',
        'architecture',
        'containerRuntimeVersion',
        'kernelVersion',
        'kubeletVersion',
        'osImage',
        'operatingSystem',
        'hostname',
        'InternalIP',
        'condition',
    ];

    protected $indexes = [
        'uid',
        'name',
    ];


    public function __construct(string $uid)
    {
        parent::__construct(self::PREFIX, $uid);

        if ($uid !== '') {
            $this->uid = $uid;
        }
    }
}
