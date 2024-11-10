<?php

namespace Yognevoy\BXUtils\Exception;

class UserNotFoundException extends \Exception
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message ?: 'User not found', $code, $previous);
    }
}
