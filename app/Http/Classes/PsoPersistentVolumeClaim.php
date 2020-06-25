<?php


namespace App\Http\Classes;

use App\Http\Classes\PsoPersistentVolume;
use App\Http\Classes\PsoPureVolume;
use Illuminate\Support\Facades\Redis;

class PsoPersistentVolumeClaim extends RedisModel
{
    public const PREFIX='pso_pvc';

    protected $fillable = [
        'uid',
        'name',
        'namespace',
        'namespace_name',
        'size',
        'storageClass',
        'labels',
        'status',

        'pv_name',
        'has_snaps',

        'pure_name',
        'pure_size',
        'pure_sizeFormatted',
        'pure_used',
        'pure_usedFormatted',
        'pure_drr',
        'pure_thinProvisioning',
        'pure_arrayName',
        'pure_arrayType',
        'pure_arrayMgmtEndPoint',
        'pure_snapshots',
        'pure_volumes',
        'pure_sharedSpace',
        'pure_totalReduction',
        'pure_orphaned',
        'pure_orphaned_state',
        'pure_orphaned_pvc_name',
        'pure_orphaned_pvc_namespace',

        'pure_reads_per_sec',
        'pure_writes_per_sec',
        'pure_input_per_sec',
        'pure_input_per_sec_formatted',
        'pure_output_per_sec',
        'pure_output_per_sec_formatted',
        'pure_usec_per_read_op',
        'pure_usec_per_write_op',
        'pure_24h_historic_used',
    ];

    protected $indexes = [
        'uid',
        'namespace',
        'namespace_name',
        'pure_orphaned',
        'labels',
    ];


    public function __construct(string $uid)
    {
        parent::__construct(SELF::PREFIX, $uid);
        if ($uid !== '') $this->uid = $uid;
    }

    public static function getUidByNamespaceName(string $namespace, string $name)
    {
        $namespaceName = $namespace . ':' . $name;

        $keys = Redis::keys(self::PREFIX . ':*:namespace_name');
        foreach ($keys as $key) {
            if (!strpos($key, '__index')) {
                $keyname = str_replace(config('database.redis.options.prefix'), '', $key);

                if (Redis::get($keyname) == $namespaceName) {
                    $keyname = str_replace(':namespace_name', ':uid', $keyname);
                    $uid = Redis::get($keyname);
                    return $uid;
                }
            }
        }
    }
}
