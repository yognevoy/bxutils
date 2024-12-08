<?php

namespace Yognevoy\BXUtils\Exception;

class IBlockNotFoundException extends \Exception
{
    public function __construct(string $iblockCode = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct("IBlock $iblockCode not found", $code, $previous);
    }
}
