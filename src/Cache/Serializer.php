<?php

/** @noinspection PhpComposerExtensionStubsInspection */

declare(strict_types=1);

namespace Vuryss\Cache;

/**
 * Class Serializer
 *
 * Handles serialization and deserialization of the cached data.
 */
class Serializer
{
    /**
     * Uses build-in PHP serialize and unserialize functions.
     */
    const METHOD_NATIVE = 'native';

    /**
     * Uses IGBinary PHP extension to serialize and deserialize data.
     */
    const METHOD_IGBINARY = 'igbinary';

    /**
     * Uses JSON PHP extension to serialize and deserialize data.
     */
    const METHOD_JSON = 'json';

    /**
     * Serialization method.
     *
     * @var string
     */
    private $method = self::METHOD_NATIVE;

    /**
     * Serializer constructor.
     *
     * @throws Exception
     *
     * @param string $method Serialization method to use. Refer to the class constants.
     */
    public function __construct(string $method)
    {
        if (!in_array($method, [self::METHOD_NATIVE, self::METHOD_IGBINARY, self::METHOD_JSON], true)) {
            throw new Exception('Invalid serialization method!');
        }

        $this->method = $method;
    }

    /**
     * Serializes the cache data.
     *
     * @throws Exception
     *
     * @param mixed $data Data to be serialized for caching purposes.
     *
     * @return string
     */
    public function serialize($data): string
    {
        switch ($this->method) {
            case self::METHOD_IGBINARY:
                return igbinary_serialize($data) ?: '';

            case self::METHOD_JSON:
                return json_encode($data) ?: '';

            default:
                return serialize($data) ?: '';
        }
    }

    /**
     * Deserializes the cache data.
     *
     * @throws Exception
     *
     * @param string $data Serialized data from the cache.
     *
     * @return mixed
     */
    public function deserialize(string $data)
    {
        switch ($this->method) {
            case self::METHOD_IGBINARY:
                $data = @igbinary_unserialize($data);

                if ($data === null) {
                    throw new Exception('Cannot deserialize data. May be cache has different data type?');
                }

                return $data;

            case self::METHOD_JSON:
                $data = @json_decode($data);

                if ($data === null) {
                    throw new Exception('Cannot deserialize data. May be cache has different data type?');
                }

                return $data;

            default:
                $data = @unserialize($data, ['allowed_classes' => false]);

                if ($data === false) {
                    throw new Exception('Cannot deserialize data. May be cache has different data type?');
                }

                return $data;
        }
    }
}
