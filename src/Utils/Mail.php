<?php

namespace Yognevoy\BXUtils\Utils;

class Mail
{
    /**
     * Resolves a valid Email from the format string:
     * "First Name Last Name <example@mail.ru >"
     *
     * @param string $email
     * @return string
     */
    public static function parseMail(string $email): string
    {
        if (str_contains($email, '<')) {
            preg_match('/<([^>]+)>/', $email, $matches);
            $parsedValue = $matches[1];
            if (!empty($parsedValue)) {
                return $parsedValue;
            }
        }
        return $email;
    }
}
