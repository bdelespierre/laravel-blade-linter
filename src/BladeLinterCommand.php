<?php

namespace Bdelespierre\LaravelBladeLinter;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;

class BladeLinterCommand extends Command
{
    protected $signature = 'blade:lint {--phpstan=} {path?*}';

    protected $description = 'Checks Blade template syntax';

    public function handle(): int
    {
        foreach ($this->getBladeFiles() as $file) {
            if (! $this->checkFile($file)) {
                $status = self::FAILURE;
            }
        }

        return $status ?? self::SUCCESS;
    }

    /**
     * @return \Generator<\SplFileInfo>
     */
    protected function getBladeFiles(): \Generator
    {
        $paths = Arr::wrap($this->argument('path') ?: Config::get('view.paths'));

        foreach ($paths as $path) {
            if (is_file($path)) {
                yield new \SplFileInfo($path);
                continue;
            }

            $it = new \RecursiveDirectoryIterator($path);
            $it = new \RecursiveIteratorIterator($it);
            /** @var \RegexIterator<never, \SplFileInfo, \RecursiveIteratorIterator<\RecursiveDirectoryIterator>> $it */
            $it = new \RegexIterator($it, '/\.blade\.php$/', \RegexIterator::MATCH);

            yield from $it;
        }
    }

    protected function checkFile(\SplFileInfo $file): bool
    {
        $code = file_get_contents($file);

        if ($code === false) {
            $this->error('Failed to open file ' . $file->getPathname());
            return false;
        }

        // compile the file and send it to the linter process
        $compiled = Blade::compileString($code);

        $errstr = '';
        $result = $this->lint($compiled, $output, $errstr);

        if (! $result) {
            $this->error(str_replace("Standard input code", $file->getPathname(), rtrim($errstr)));
            return false;
        }

        if ((bool) $this->option('phpstan') && count($errors = $this->analyse($compiled)) > 0) {
            foreach ($errors as $error) {
                $this->error("PHPStan error:  {$error->message} in {$file->getPathname()} on line {$error->line}");
            }
            return false;
        }

        if ($this->getOutput()->isVerbose()) {
            $this->line("No syntax errors detected in {$file->getPathname()}");
        }

        return true;
    }

    protected function lint(string $code, ?string &$stdout = "", ?string &$stderr = ""): bool
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

    /**
     * @return list<ErrorRecord>
     */
    protected function analyse(string $code): array
    {
        // write to a temporary file
        // (phpstan doesn't support stdin)
        $path = tempnam(sys_get_temp_dir(), 'laravel-blade-linter');

        if (false === $path || false === file_put_contents($path, $code)) {
            throw new \RuntimeException("unable to write to {$path}");
        }

        try {
            return $this->analyseFile($path);
        } finally {
            unlink($path);
        }
    }

    /**
     * @return list<ErrorRecord>
     */
    protected function analyseFile(string $path): array
    {
        $errors = [];

        $phpstan = ($this->option('phpstan') ?: '');

        if (!is_string($phpstan)) {
            throw new \InvalidArgumentException("invalid phpstan path: " . gettype($phpstan));
        }

        if (!is_executable($phpstan)) {
            throw new \RuntimeException("unable to run {$phpstan}");
        }

        ob_start(); // shell_exec echoes stderr...
        $output = shell_exec("{$phpstan} analyse --error-format json --no-ansi --no-progress -- {$path} 2>/dev/null");
        $stderr = ob_get_clean();

        $json = json_decode((string) $output, flags: JSON_THROW_ON_ERROR);

        if (! $json instanceof \stdClass) {
            throw new \RuntimeException("unable to parse PHPStan output");
        }

        foreach ($json->files as $filename => $descriptor) {
            foreach ($descriptor->messages as $message) {
                $message->message = rtrim(lcfirst($message->message), '.');
                $errors[] = new ErrorRecord($message->message, $path, $message->line);
            }
        }

        return $errors;
    }
}
