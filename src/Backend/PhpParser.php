<?php
declare(strict_types=1);

namespace Bdelespierre\LaravelBladeLinter\Backend;

use Bdelespierre\LaravelBladeLinter\Backend;
use Bdelespierre\LaravelBladeLinter\ErrorRecord;
use PhpParser\Error;
use PhpParser\ParserFactory;

final class PhpParser implements Backend
{
    /**
     * @param \SplFileInfo $file
     * @param string $code
     * @return list<ErrorRecord>
     */
    public function analyze(\SplFileInfo $file, string $code): array
    {
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        try {
            $parser->parse($code);
        } catch (Error $e) {
            return [
                new ErrorRecord(
                    'Parse error: ' . $e->getRawMessage(),
                    $file->getPathname(),
                    $e->getStartLine()
                )
            ];
        }

        return [];
    }
}
