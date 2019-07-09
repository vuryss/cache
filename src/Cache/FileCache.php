<?php

declare(strict_types=1);

namespace Vuryss\Cache;

use DateInterval;
use DateTime;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Provides simple PSR-16 cache by using local filesystem.
 */
class FileCache extends Cache implements CacheInterface
{
    /**
     * Location of the cache file.
     *
     * @var string
     */
    private $filename;

    /**
     * Timestamp when the cache file has been modified for the last time.
     *
     * @var integer
     */
    private $lastModificationTime = 0;

    /**
     * @var array
     */
    private $data = [];

    /**
     * FileCache constructor.
     *
     * @throws Exception
     *
     * @param string      $filename        Absolute file path to the cache file to use.
     * @param string|null $serializeMethod Which cache method to use, refer to constants in Serializer class.
     */
    public function __construct(string $filename, string $serializeMethod = null)
    {
        // Check the cache file, creating it if needed.
        if (!file_exists($filename)) {
            if (!touch($filename)) {
                throw new Exception('Cannot create or use the cache file: ' . $filename);
            }
        }

        if (!is_writable($filename)) {
            throw new Exception('Cache files is not writable: ' . $filename);
        }

        $this->filename             = $filename;
        $this->lastModificationTime = filemtime($this->filename);

        // Load serializer.
        $this->loadSerializer($serializeMethod);
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

        $data = $this->getData()[$key] ?? null;

        if (!$data) {
            return $default;
        }

        if ($data['ttl'] === 0 || $data['ttl'] >= time()) {
            return $data['value'];
        }

        return $default;
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
     * @return boolean True on success and false on failure.
     */
    public function set($key, $value, $ttl = null)
    {
        $this->validateKey($key);

        if ($ttl instanceof DateInterval) {
            $ttl = (new DateTime())->add($ttl)->getTimestamp();
        }

        $data       = $this->getData();
        $data[$key] = [
            'ttl'   => is_int($ttl) ? time() + $ttl : 0,
            'value' => $value,
        ];

        return $this->saveData($data);
    }

    /**
     * Delete an item from the cache by its unique key.
     *
     * @throws InvalidArgumentException - thrown if the $key string is not a legal value.
     *
     * @param string $key The unique cache key of the item to delete.
     *
     * @return boolean True if the item was successfully removed. False if there was an error.
     */
    public function delete($key)
    {
        $this->validateKey($key);

        $data = $this->getData();

        if (isset($data[$key])) {
            unset($data[$key]);
        }

        return $this->saveData($data);
    }

    /**
     * Wipes clean the entire cache's keys.
     *
     * @return boolean True on success and false on failure.
     */
    public function clear()
    {
        return $this->saveData([]);
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

        $data = $this->getData();

        $result = [];

        foreach ($keys as $key) {
            $this->validateKey($key);

            if (isset($data[$key]) && ($data[$key]['ttl'] === 0 || $data[$key]['ttl'] >= time())) {
                $result[$key] = $data[$key]['value'];
                continue;
            }

            $result[$key] = $default;
        }

        return $result;
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
     * @return boolean True on success and false on failure.
     */
    public function setMultiple($values, $ttl = null)
    {
        if (!is_iterable($values)) {
            throw new Exception('Data is neither an array nor a Traversable');
        }

        $data = $this->getData();

        if ($ttl instanceof DateInterval) {
            $ttl = (new DateTime())->add($ttl)->getTimestamp();
        }

        foreach ($values as $key => $value) {
            $this->validateKey($key);

            $data[$key] = [
                'ttl'   => is_int($ttl) ? time() + $ttl : 0,
                'value' => $value,
            ];
        }

        return $this->saveData($data);
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
     * @return boolean True if the items were successfully removed. False if there was an error.
     */
    public function deleteMultiple($keys)
    {
        $data = $this->getData();

        if (!is_iterable($keys)) {
            throw new Exception('Keys is neither an array nor a Traversable');
        }

        foreach ($keys as $key) {
            $this->validateKey($key);

            if (isset($data[$key])) {
                unset($data[$key]);
            }
        }

        return $this->saveData($data);
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
     * @return boolean
     */
    public function has($key)
    {
        $this->validateKey($key);

        $data = $this->getData()[$key] ?? null;

        if (!$data) {
            return false;
        }

        if ($data['ttl'] === 0 || $data['ttl'] >= time()) {
            return true;
        }

        return false;
    }

    /**
     * Retrieves data from the cache file.
     *
     * @throws Exception
     *
     * @return array
     */
    private function getData(): array
    {
        if (!empty($this->data) && $this->lastModificationTime === filemtime($this->filename)) {
            return $this->data;
        }

        $fileContent                = file_get_contents($this->filename);
        $this->lastModificationTime = filemtime($this->filename);
        $this->data                 = [];

        if (!empty($fileContent)) {
            $data       = $this->serializer->deserialize($fileContent);
            $this->data = empty($data) ? [] : $data;
        }

        return $this->data;
    }

    /**
     * Saves data to the file
     *
     * @param array $data Data to be saved in the cache file.
     *
     * @return boolean
     */
    private function saveData(array $data): bool
    {
        $data = $this->serializer->serialize($data);
        $this->lastModificationTime = -1;
        return file_put_contents($this->filename, $data, LOCK_EX) !== false;
    }
}
