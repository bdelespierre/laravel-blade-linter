<?php
declare(strict_types=1);

namespace Bdelespierre\LaravelBladeLinter;

/**
 * @internal
 */
final class ErrorRecord
{
    public function __construct(
        public string $message,
        public string $path,
        public ?int $line = null
    ) {
    }

    public function toString(): string
    {
        return "{$this->message} in {$this->path} on line {$this->line}";
    }
}
