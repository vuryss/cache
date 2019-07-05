<?php

declare(strict_types=1);

namespace Vuryss\Cache;

use Psr\SimpleCache\InvalidArgumentException;

class Exception extends \Exception implements InvalidArgumentException
{

}
