<?php

declare(strict_types=1);

namespace Vuryss\Cache;

class Serializer
{
    /**
     * Uses build-in PHP serialize and unserialize functions.
     */
    const METHOD_NATIVE   = 'native';

    /**
     * Uses IGBinary PHP extension to serialize and deserialize data.
     */
    const METHOD_IGBINARY = 'igbinary';

    /**
     * Uses JSON PHP extension to serialize and deserialize data.
     */
    const METHOD_JSON     = 'json';

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
     * @param string $method
     */
    public function __construct(string $method)
    {
        if (!in_array($method, [self::METHOD_NATIVE, self::METHOD_IGBINARY, self::METHOD_JSON])) {
            throw new Exception('Invalid serialization method!');
        }

        $this->method = $method;
    }


    public function serialize($data): string
    {
        switch ($this->method) {
            default:
                return serialize($data);

            case self::METHOD_IGBINARY:
                /** @noinspection PhpComposerExtensionStubsInspection */
                return igbinary_serialize($data);

            case self::METHOD_JSON:
                /** @noinspection PhpComposerExtensionStubsInspection */
                return json_encode($data);
        }
    }

    /**
     * @throws Exception
     *
     * @param string $data
     *
     * @return mixed
     */
    public function deserialize($data)
    {
        switch ($this->method) {
            default:
                $data = @unserialize($data, ['allowed_classes' => false]);

                if ($data === false) {
                    throw new Exception('Cannot deserialize data. May be cache has different data type?');
                }

                return $data;

            case self::METHOD_IGBINARY:
                /** @noinspection PhpComposerExtensionStubsInspection */
                $data = @igbinary_unserialize($data);

                if ($data === null) {
                    throw new Exception('Cannot deserialize data. May be cache has different data type?');
                }

                return $data;

            case self::METHOD_JSON:
                /** @noinspection PhpComposerExtensionStubsInspection */
                $data = @json_decode($data);

                if ($data === null) {
                    throw new Exception('Cannot deserialize data. May be cache has different data type?');
                }

                return $data;
        }
    }
}
