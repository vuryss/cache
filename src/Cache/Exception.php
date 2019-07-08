<?php

declare(strict_types=1);

namespace Vuryss\Cache;

use Psr\SimpleCache\InvalidArgumentException;

/**
 * The only Exception class which should be thrown in this library.
 */
class Exception extends \Exception implements InvalidArgumentException
{

}
