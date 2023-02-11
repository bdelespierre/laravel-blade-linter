<?php
declare(strict_types=1);

namespace Bdelespierre\LaravelBladeLinter;

interface Backend
{
    /**
     * @param \SplFileInfo $file
     * @param string $code
     * @return list<ErrorRecord>
     */
    public function analyze(\SplFileInfo $file, string $code): array;

    public static function name(): string;
}
