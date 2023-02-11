<?php
declare(strict_types=1);

namespace Bdelespierre\LaravelBladeLinter\Backend;

use Bdelespierre\LaravelBladeLinter\Backend;
use Bdelespierre\LaravelBladeLinter\ErrorRecord;

final class Evaluate implements Backend
{
    /**
     * @param \SplFileInfo $file
     * @param string $code
     * @return list<ErrorRecord>
     */
    public function analyze(\SplFileInfo $file, string $code): array
    {
        $code = 'function() { ?>' . $code . '<?php };';

        try {
            eval($code);
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

    public static function name(): string
    {
        return 'eval';
    }
}
