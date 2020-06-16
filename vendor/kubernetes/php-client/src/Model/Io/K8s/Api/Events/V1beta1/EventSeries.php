<?php

namespace Kubernetes\Model\Io\K8s\Api\Events\V1beta1;

/**
 * EventSeries contain information on series of events, i.e. thing that was/is
 * happening continuously for some time.
 */
class EventSeries extends \KubernetesRuntime\AbstractModel
{

    /**
     * Number of occurrences in this series up to the last heartbeat time
     *
     * @var integer
     */
    public $count = null;

    /**
     * Time when last Event from the series was seen before last heartbeat.
     *
     * @var \Kubernetes\Model\Io\K8s\Apimachinery\Pkg\Apis\Meta\V1\MicroTime
     */
    public $lastObservedTime = null;

    /**
     * Information whether this series is ongoing or finished. Deprecated. Planned
     * removal for 1.18
     *
     * @var string
     */
    public $state = null;


}
