<?php
declare(strict_types=1);

namespace Bdelespierre\LaravelBladeLinter\Backend;

use Bdelespierre\LaravelBladeLinter\Backend;
use Bdelespierre\LaravelBladeLinter\ErrorRecord;

final class Cli implements Backend
{
    private const REGEX = '/Standard input code on line (\d+)[\s\r\n]*/';

    /**
     * @param \SplFileInfo $file
     * @param string $code
     * @return list<ErrorRecord>
     */
    public function analyze(\SplFileInfo $file, string $code): array
    {
        $message = '';
        $result = $this->lint($code, $output, $message);

        if (! $result) {
            $line = null;
            if (false !== preg_match(self::REGEX, trim($message), $matches)) {
                $line = isset($matches[1]) ? (int) $matches[1] : null;
                $message = preg_replace(self::REGEX, '', $message);
            }

            return [
                new ErrorRecord(
                    $message,
                    $file->getPathname(),
                    $line
                ),
            ];
        }

        return [];
    }

    public static function name(): string
    {
        return 'cli';
    }

    private function lint(string $code, ?string &$stdout = "", ?string &$stderr = ""): bool
    {
        $descriptors = [
            0 => ["pipe", "r"], // read from stdin
            1 => ["pipe", "w"], // write to stdout
            2 => ["pipe", "w"], // write to stderr
        ];

        // open linter process (php -l)
        $process = proc_open('php -d display_errors=stderr -l', $descriptors, $pipes);

        if (! is_resource($process)) {
            throw new \RuntimeException("unable to open process 'php -l'");
        }

        fwrite($pipes[0], $code);
        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        // it is important that you close any pipes before calling
        // proc_close in order to avoid a deadlock
        $retval = proc_close($process);

        // zero actually means "no error"
        return $retval === 0;
    }
}
