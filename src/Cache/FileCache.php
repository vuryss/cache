<?php

declare(strict_types=1);

namespace Vuryss\Cache;

use DateInterval;
use DateTime;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

class FileCache extends Cache implements CacheInterface
{
    /**
     * Location of the cache file.
     *
     * @var string
     */
    private $filename;


    private $lastModificationTime;

    /**
     * @var array
     */
    private $data = [];

    /**
     * FileCache constructor.
     *
     * @throws Exception
     *
     * @param string      $filename
     * @param string|null $serializeMethod
     */
    public function __construct(string $filename, string $serializeMethod = null)
    {
        // Check the cache file, creating it if needed
        if (!file_exists($filename)) {
            if (!touch($filename)) {
                throw new Exception('Cannot create or use the cache file: ' . $filename);
            }
        } elseif (!is_writable($filename)) {
            throw new Exception('Cache files is not writable: ' . $filename);
        }

        $this->filename = $filename;
        $this->lastModificationTime = filemtime($this->filename);

        // Load serializer
        $serializeMethods = [Serializer::METHOD_NATIVE, Serializer::METHOD_IGBINARY, Serializer::METHOD_JSON];

        if ($serializeMethod) {
            if (!in_array($serializeMethod, $serializeMethods)) {
                throw new Exception('Invalid serialization method!');
            }

            $this->serializeMethod = $serializeMethod;
        }

        $this->serializer = new Serializer($this->serializeMethod);
    }

    /**
     * Fetches a value from the cache.
     *
     * @throws InvalidArgumentException - thrown if the $key string is not a legal value.
     *
     * @param mixed  $default Default value to return if the key does not exist.
     *
     * @param string $key     The unique key of this item in the cache.
     *
     * @return mixed The value of the item from the cache, or $default in case of cache miss.
     *
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
     * @param mixed                 $value  The value of the item to store, must be serializable.
     * @param null|int|DateInterval $ttl    Optional. The TTL value of this item. If no value is sent and
     *                                      the driver supports TTL then the library may set a default value
     *                                      for it or let the driver take care of that.
     *
     * @param string                $key    The key of the item to store.
     *
     * @return bool True on success and false on failure.
     *
     */
    public function set($key, $value, $ttl = null)
    {
        $this->validateKey($key);

        if ($ttl instanceof DateInterval) {
            $ttl = (new DateTime())->add($ttl)->getTimestamp();
        } else {
            $ttl = is_int($ttl) ? time() + $ttl : 0;
        }

        $data = $this->getData();
        $data[$key] = [
            'ttl' => $ttl,
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
     * @return bool True if the item was successfully removed. False if there was an error.
     *
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
     * @return bool True on success and false on failure.
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
     * @param mixed    $default Default value to return for keys that do not exist.
     *
     * @param iterable $keys    A list of keys that can obtained in a single operation.
     *
     * @return iterable A list of key => value pairs.
     *         Cache keys that do not exist or are stale will have $default as value.
     */
    public function getMultiple($keys, $default = null)
    {
        $data = $this->getData();

        if (!is_iterable($keys)) {
            throw new Exception('Keys is neither an array nor a Traversable');
        }

        $result = [];

        foreach ($keys as $key) {
            $this->validateKey($key);

            if (!isset($data[$key])) {
                $result[$key] = $default;
            } elseif ($data[$key]['ttl'] === 0 || $data[$key]['ttl'] >= time()) {
                $result[$key] = $data[$key]['value'];
            } else {
                $result[$key] = $default;
            }
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
     * @param null|int|DateInterval $ttl     Optional. The TTL value of this item. If no value is sent and
     *                                       the driver supports TTL then the library may set a default value
     *                                       for it or let the driver take care of that.
     *
     * @param iterable              $values  A list of key => value pairs for a multiple-set operation.
     *
     * @return bool True on success and false on failure.
     *
     */
    public function setMultiple($values, $ttl = null)
    {
        $data = $this->getData();

        if ($ttl instanceof DateInterval) {
            $ttl = (new DateTime())->add($ttl)->getTimestamp();
        } else {
            $ttl = is_int($ttl) ? time() + $ttl : 0;
        }

        if (!is_iterable($values)) {
            throw new Exception('Data is neither an array nor a Traversable');
        }

        foreach ($values as $key => $value) {
            $this->validateKey($key);

            $data[$key] = [
                'ttl' => $ttl,
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
     * @return bool True if the items were successfully removed. False if there was an error.
     *
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
     * @return bool
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
        if ($this->lastModificationTime < filemtime($this->filename) || empty($this->data)) {
            $fileContent = file_get_contents($this->filename);

            if (empty($fileContent)) {
                $this->data = [];
            } else {
                $data = $this->serializer->deserialize($fileContent);
                $this->data = empty($data) ? [] : $data;
            }
        }

        return $this->data;
    }

    /**
     * Saves data to the file
     *
     * @param array $data
     *
     * @return bool
     */
    private function saveData(array $data): bool
    {
        $data = $this->serializer->serialize($data);
        return file_put_contents($this->filename, $data, LOCK_EX) !== false;
    }
}
