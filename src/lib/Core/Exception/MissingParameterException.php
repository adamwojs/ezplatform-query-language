<?php

declare(strict_types=1);

namespace EzSystems\EzPlatformQueryLanguage\Core\Exception;

use Exception;
use Throwable;

final class MissingParameterException extends Exception
{
    public function __construct(string $parameterName, $code = 0, Throwable $previous = null)
    {
        parent::__construct("Missing parameter: $parameterName", $code, $previous);
    }
}
