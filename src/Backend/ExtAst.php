<?php
declare(strict_types=1);

namespace Bdelespierre\LaravelBladeLinter\Backend;

use Bdelespierre\LaravelBladeLinter\Backend;
use Bdelespierre\LaravelBladeLinter\ErrorRecord;
use function ast\parse_code;

final class ExtAst implements Backend
{
    public function __construct(
        private int $astVersion = 85
    ) {
    }

    /**
     * @param \SplFileInfo $file
     * @param string $code
     * @return list<ErrorRecord>
     */
    public function analyze(\SplFileInfo $file, string $code): array
    {
        try {
            parse_code($code, $this->astVersion);
        } catch (\ParseError $e) {
            return [
                new ErrorRecord(
                    'Parse error: ' . $e->getMessage(),
                    $file->getPathname(),
                    $e->getLine()
                )
            ];
        }

        return [];
    }
}
