<?php

/** @noinspection PhpComposerExtensionStubsInspection */
/** @noinspection PhpRedundantCatchClauseInspection */

declare(strict_types=1);

namespace Vuryss\Cache;

use DateInterval;
use DateTime;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Redis;
use RedisException;

/**
 * Provides simple PSR-16 cache by using a redis server.
 * It uses the redis PHP Extension.
 */
class RedisCache extends Cache implements CacheInterface
{
    /**
     * Redis client, class from the PHP Extension
     *
     * @var Redis
     */
    private $redisClient;

    /**
     * RedisCache constructor.
     *
     * @throws Exception When connection to Redis server cannot be established.
     *
     * @param string|null  $hostname        Hostname of the Redis server.
     * @param integer|null $port            Port number.
     * @param float|null   $timeout         Timeout in seconds after which it will stop trying to connect.
     * @param string|null  $serializeMethod Serialization method for the saved data. One of Serializer's constants.
     * @param mixed|null   $redisClient     Custom redis instance, for mocking or decorating original one.
     */
    public function __construct(
        ?string $hostname = null,
        ?int $port = null,
        ?float $timeout = null,
        ?string $serializeMethod = null,
        $redisClient = null
    ) {
        $hostname          = $hostname ?? '127.0.0.1';
        $port              = $port ?? 6379;
        $timeout           = $timeout ?? 3.0;
        $this->redisClient = $redisClient ?? new Redis();
        $this->loadSerializer($serializeMethod);

        try {
            $isConnected = $redisClient->ping() === '+PONG';
        } catch (RedisException $e) {
            $isConnected = false;
        }

        if (!$isConnected) {
            try {
                $isConnected = $this->redisClient->pconnect($hostname, $port, $timeout);
            } catch (RedisException $e) {
                throw new Exception('Could not connect to Redis server. Exception: ' . $e->getMessage());
            }
        }

        if (!$isConnected) {
            throw new Exception('Could not connect to Redis server.');
        }
    }

    /**
     * Fetches a value from the cache.
     *
     * @throws InvalidArgumentException - thrown if the $key string is not a legal value.
     *
     * @param string $key     The unique key of this item in the cache.
     * @param mixed  $default Default value to return if the key does not exist.
     *
     * @return mixed The value of the item from the cache, or $default in case of cache miss.
     */
    public function get($key, $default = null)
    {
        $this->validateKey($key);

        $data = $this->redisClient->get($key);

        if ($data === false) {
            return $default;
        }

        return $this->serializer->deserialize($data);
    }

    /**
     * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
     *
     * @throws InvalidArgumentException - thrown if the $key string is not a legal value.
     *
     * @param string                    $key   The key of the item to store.
     * @param mixed                     $value The value of the item to store, must be serializable.
     * @param null|integer|DateInterval $ttl   Optional. The TTL value of this item. If no value is sent and
     *                                         the driver supports TTL then the library may set a default value
     *                                         for it or let the driver take care of that.
     *
     * @return bool True on success and false on failure.
     */
    public function set($key, $value, $ttl = null)
    {
        $this->validateKey($key);

        if ($ttl instanceof DateInterval) {
            $ttl = (new DateTime())->add($ttl)->getTimestamp() - time();
        }

        if (is_int($ttl) && $ttl >= 0) {
            return $this->redisClient->setex($key, $ttl, $this->serializer->serialize($value)) === true;
        }

        return $this->redisClient->set($key, $this->serializer->serialize($value)) === true;
    }

    /**
     * Delete an item from the cache by its unique key.
     *
     * @throws InvalidArgumentException - thrown if the $key string is not a legal value.
     *
     * @param string $key The unique cache key of the item to delete.
     *
     * @return bool True if the item was successfully removed. False if there was an error.
     */
    public function delete($key)
    {
        $this->validateKey($key);

        $result = $this->redisClient->del($key);

        return is_int($result) && $result >= 0;
    }

    /**
     * Wipes clean the entire cache's keys.
     *
     * @return bool True on success and false on failure.
     */
    public function clear()
    {
        return $this->redisClient->flushDB();
    }

    /**
     * Obtains multiple cache items by their unique keys.
     *
     * @throws InvalidArgumentException
     *   thrown if $keys is neither an array nor a Traversable,
     *   or if any of the $keys are not a legal value.
     *
     * @param iterable $keys    A list of keys that can obtained in a single operation.
     * @param mixed    $default Default value to return for keys that do not exist.
     *
     * @return iterable A list of key => value pairs.
     *         Cache keys that do not exist or are stale will have $default as value.
     */
    public function getMultiple($keys, $default = null)
    {
        if (!is_iterable($keys)) {
            throw new Exception('Keys is neither an array nor a Traversable');
        }

        $validKeys = [];

        foreach ($keys as $key) {
            $this->validateKey($key);
            $validKeys[] = $key;
        }

        $results = $this->redisClient->mget($validKeys);

        foreach ($results as $index => $result) {
            unset($results[$index]);

            if ($result === false) {
                $results[$validKeys[$index]] = $default;
                continue;
            }

            $results[$validKeys[$index]] = $this->serializer->deserialize($result);
        }

        return $results;
    }

    /**
     * Persists a set of key => value pairs in the cache, with an optional TTL.
     *
     * @throws InvalidArgumentException
     *   thrown if $values is neither an array nor a Traversable,
     *   or if any of the $values are not a legal value.
     *
     * @param iterable                  $values A list of key => value pairs for a multiple-set operation.
     * @param null|integer|DateInterval $ttl    Optional. The TTL value of this item. If no value is sent and
     *                                          the driver supports TTL then the library may set a default value
     *                                          for it or let the driver take care of that.
     *
     * @return bool True on success and false on failure.
     */
    public function setMultiple($values, $ttl = null)
    {
        if (!is_iterable($values)) {
            throw new Exception('Data is neither an array nor a Traversable');
        }

        if ($ttl instanceof DateInterval) {
            $ttl = (new DateTime())->add($ttl)->getTimestamp() - time();
        }

        $arrayValues = [];

        foreach ($values as $key => $value) {
            $this->validateKey($key);
            $arrayValues[$key] = $this->serializer->serialize($value);
        }

        if (!$this->redisClient->mset($arrayValues)) {
            return false;
        }

        if (is_int($ttl) && $ttl >= 0) {
            $result = 1;

            foreach ($arrayValues as $key => $value) {
                $result &= $this->redisClient->expire($key, $ttl) === true;
            }

            return $result === 1;
        }

        return true;
    }

    /**
     * Deletes multiple cache items in a single operation.
     *
     * @throws InvalidArgumentException
     *   thrown if $keys is neither an array nor a Traversable,
     *   or if any of the $keys are not a legal value.
     *
     * @param iterable $keys A list of string-based keys to be deleted.
     *
     * @return bool True if the items were successfully removed. False if there was an error.
     */
    public function deleteMultiple($keys)
    {
        if (!is_iterable($keys)) {
            throw new Exception('Keys is neither an array nor a Traversable');
        }

        foreach ($keys as $key) {
            $this->validateKey($key);
        }

        return $this->redisClient->del(...$keys);
    }

    /**
     * Determines whether an item is present in the cache.
     *
     * NOTE: It is recommended that has() is only to be used for cache warming type purposes
     * and not to be used within your live applications operations for get/set, as this method
     * is subject to a race condition where your has() will return true and immediately after,
     * another script can remove it making the state of your app out of date.
     *
     * @throws InvalidArgumentException - thrown if the $key string is not a legal value.
     *
     * @param string $key The cache item key.
     *
     * @return bool
     */
    public function has($key)
    {
        $this->validateKey($key);

        return $this->redisClient->exists($key) === 1;
    }
}
