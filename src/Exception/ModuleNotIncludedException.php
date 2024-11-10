<?php

namespace Yognevoy\BXUtils\Exception;

class ModuleNotIncludedException extends \Exception
{
    public function __construct(string $module = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct("Module $module not included", $code, $previous);
    }
}
