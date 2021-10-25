<?php

declare(strict_types=1);

namespace Lazy\Csv;

use Exception;

class CsvFileException extends Exception
{
    public static function create(string $message): self
    {
        return new self($message);
    }
}
