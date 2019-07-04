<?php

declare(strict_types=1);

namespace Vuryss\Cache;

class FileCache
{
    /**
     * Location of the cache file
     *
     * @var string
     */
    private $filename;

    /**
     * FileCache constructor.
     *
     * @param string $filename
     */
    public function __construct(string $filename)
    {
        $this->filename = $filename;
    }
}
