<?php

namespace App\Http\Classes;

use Illuminate\Support\Facades\Redis;

class RedisModel
{
    private $redisPrefix = null;
    private $redisUid = null;
    private $data = [];
    protected $indexes = [];
    protected $fillable = [];

    public function __construct(string $redisPrefix, string $redisUid)
    {
        $this->redisPrefix = $redisPrefix;
        $this->redisUid = $redisUid;
    }

    public function __set($name, $value)
    {
        if (in_array($name, $this->fillable)) {
            if (is_array($value)) {
                Redis::del($this->redisPrefix . ':' . $this->redisUid . ':' . $name);
                foreach ($value as $item) {
                    Redis::rpush($this->redisPrefix . ':' . $this->redisUid . ':' . $name, $item);
                    if (in_array($name, $this->indexes) and ($value !== '') and ($value !== null)) {
                        Redis::sadd($this->redisPrefix . ':__index:' . $name, $item);
                    }
                }
            } else {
                Redis::set($this->redisPrefix . ':' . $this->redisUid . ':' . $name, $value);
                if (in_array($name, $this->indexes) and ($value !== '') and ($value !== null)) {
                    Redis::sadd($this->redisPrefix . ':__index:' . $name, $value);
                }
            }
            $this->data[$name] = $value;
        }
    }

    public function __get($name)
    {
        if (in_array($name, $this->fillable)) {
            switch (Redis::type($this->redisPrefix . ':' . $this->redisUid . ':' . $name)) {
                case 1:
                    return Redis::get($this->redisPrefix . ':' . $this->redisUid . ':' . $name);
                    break;
                case 3:
                    return Redis::lrange($this->redisPrefix . ':' . $this->redisUid . ':' . $name, 0, -1);
                    break;
            }
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

    public static function items(string $redisPrefix, string $name)
    {
        if (Redis::exists($redisPrefix . ':__index:' . $name)) {
            return Redis::sort($redisPrefix . ':__index:' . $name, ['ALPHA' => true, 'sort' => 'ASC']);
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
