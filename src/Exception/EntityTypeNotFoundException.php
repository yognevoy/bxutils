<?php

namespace Yognevoy\BXUtils\Exception;

class EntityTypeNotFoundException extends \Exception
{
    public function __construct(string $typeCode = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct("Entity type $typeCode not found", $code, $previous);
    }
}
