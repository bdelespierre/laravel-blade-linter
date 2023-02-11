<?php

namespace Bdelespierre\LaravelBladeLinter;

/**
 * @internal
 */
final class ErrorRecord
{
    public function __construct(
        public string $message,
        public string $path,
        public ?int $line
    ) {
    }
}
