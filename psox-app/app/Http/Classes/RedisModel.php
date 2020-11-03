<?php

namespace App\Http\Classes;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class RedisModel
{
    private $redisPrefix = null;
    private $redisUid = null;
    private $data = [];
    protected $indexes = [];
    protected $fillable = [];

    function isAssoc(array $arr)
    {
        if (array() === $arr) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    public function __construct(string $redisPrefix, string $redisUid)
    {
        $this->redisPrefix = $redisPrefix;
        $this->redisUid = $redisUid;
    }

    public function __set($name, $value)
    {
        if (in_array($name, $this->fillable)) {
            if (is_array($value)) {
                if ($this->isAssoc($value)) {
                    // If this is a Key/Value array, use Redis hash to store the array
                    Redis::del($this->redisPrefix . ':' . $this->redisUid . ':' . $name);
                    foreach ($value as $k => $v) {
                        Redis::hset($this->redisPrefix . ':' . $this->redisUid . ':' . $name, $k, $v);
                    }
                } else {
                    // If this is a regular array, use Redis list to store the array
                    Redis::del($this->redisPrefix . ':' . $this->redisUid . ':' . $name);
                    foreach ($value as $item) {
                        Redis::rpush($this->redisPrefix . ':' . $this->redisUid . ':' . $name, $item);
                        if (in_array($name, $this->indexes) and ($value !== '') and ($value !== null)) {
                            Redis::sadd($this->redisPrefix . ':__index:' . $name, $item);
                        }
                    }
                }
            } else {
                Redis::set($this->redisPrefix . ':' . $this->redisUid . ':' . $name, $value);
                if (in_array($name, $this->indexes) and ($value !== '') and ($value !== null)) {
                    Redis::sadd($this->redisPrefix . ':__index:' . $name, $value);
                }
            }
            $this->data[$name] = $value;
        } else {
            Log::debug('      Error trying to save field "' . $name . '" since it\'s not in fillable');
        }
    }

    public function __get($name)
    {
        if (in_array($name, $this->fillable)) {
            $type = Redis::type($this->redisPrefix . ':' . $this->redisUid . ':' . $name);
            switch ($type) {
                case 0: // REDIS_NOT_FOUND (https://github.com/phpredis/phpredis)
                    // We need this when a "empty" variable is accessed
                    return null;
                    break;
                case 1: // REDIS_STRING (https://github.com/phpredis/phpredis)
                    return Redis::get($this->redisPrefix . ':' . $this->redisUid . ':' . $name);
                    break;
                case 3: // REDIS_LIST (https://github.com/phpredis/phpredis)
                    return Redis::lrange($this->redisPrefix . ':' . $this->redisUid . ':' . $name, 0, -1);
                    break;
                case 5: // REDIS_HASH (https://github.com/phpredis/phpredis)
                    return Redis::hgetall($this->redisPrefix . ':' . $this->redisUid . ':' . $name);
                    break;
                default:
                    $message = '      Redis type "' . $type .
                        '" for variable "' . $name .
                        '" is unknown in prefix "' . $this->redisPrefix .
                        '". No data returned.';
                    Log::debug($message);
                    return null;
                    break;
            }
        } else {
            Log::debug('      Error trying to access field "' . $name . '" since it\'s not in fillable');
        }
    }

    public function delete()
    {
        // TODO: Ideally I'd use SCAN here instead of KEYS, since KEYS blocks redis
        foreach (Redis::keys($this->redisPrefix . ':' . $this->redisUid . ':*') as $key) {
            $keyname = str_replace(config('database.redis.options.prefix'), '', $key);
            Redis::del($keyname);
        }
        foreach ($this->indexes as $index) {
            if (is_array($this->data[$index])) {
                foreach ($this->data[$index] as $item) {
                    Redis::srem($this->redisPrefix . ':__index:' . $index, $item);
                }
            } else {
                Redis::srem($this->redisPrefix . ':__index:' . $index, $this->data[$index]);
            }
        }
    }

    public static function items(string $redisPrefix, string $index)
    {
        if (Redis::exists($redisPrefix . ':__index:' . $index)) {
            return Redis::sort($redisPrefix . ':__index:' . $index, ['ALPHA' => true, 'sort' => 'ASC']);
        } else {
            return [];
        }
    }

    public static function deleteAll(string $redisPrefix)
    {
        // TODO: Ideally I'd use SCAN here instead of KEYS, since KEYS blocks redis
        foreach (Redis::keys($redisPrefix . ':*') as $key) {
            $keyname = str_replace(config('database.redis.options.prefix'), '', $key);
            Redis::del($keyname);
        }
    }

    public function arrayPush($name, $value)
    {
        if (!isset($this->data[$name])) {
            $this->data[$name] = [];
        }

        if (in_array($name, $this->fillable)) {
            if (is_array($value)) {
                foreach ($value as $item) {
                    Redis::rpush($this->redisPrefix . ':' . $this->redisUid . ':' . $name, $item);
                    array_push($this->data[$name], $item);
                    if (in_array($name, $this->indexes) and ($value !== '') and ($value !== null)) {
                        Redis::sadd($this->redisPrefix . ':__index:' . $name, $item);
                    }
                }
            } else {
                Redis::rpush($this->redisPrefix . ':' . $this->redisUid . ':' . $name, $value);
                array_push($this->data[$name], $value);
                if (in_array($name, $this->indexes) and ($value !== '') and ($value !== null)) {
                    Redis::sadd($this->redisPrefix . ':__index:' . $name, $value);
                }
            }
        }
    }

    public function asArray()
    {
        $array = [];
        foreach ($this->fillable as $item) {
            if ($item !== 'uid') {
                $array[$item] = $this->__get($item);
            }
        }
        return $array;
    }
}
