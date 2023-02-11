<?php
declare(strict_types=1);

namespace Bdelespierre\LaravelBladeLinter;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;

final class BladeLinterCommand extends Command
{
    protected $signature = 'blade:lint {path?*}';

    protected $description = 'Checks Blade template syntax';

    public function handle(): int
    {
        foreach ($this->getBladeFiles() as $file) {
            $errors = $this->checkFile($file);
            if (count($errors) > 0) {
                $status = self::FAILURE;
                foreach ($errors as $error) {
                    $this->error($error->toString());
                }
            } elseif ($this->getOutput()->isVerbose()) {
                $this->line("No syntax errors detected in {$file->getPathname()}");
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

    /**
     * @param \SplFileInfo $file
     * @return list<ErrorRecord>
     */
    private function checkFile(\SplFileInfo $file): array
    {
        $code = file_get_contents($file->getPathname());

        if ($code === false) {
            throw new \RuntimeException('Failed to open file ' . $file->getPathname());
        }

        // compile the file and send it to the linter process
        $compiled = Blade::compileString($code);

        $backends = [new Backend\Cli()];

        $errors = [];

        foreach ($backends as $backend) {
            $errors += $backend->analyze($file, $compiled);
        }

        return $errors;
    }
}
