<?php
declare(strict_types=1);

namespace Bdelespierre\LaravelBladeLinter;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use PhpParser\ParserFactory;

final class BladeLinterCommand extends Command
{
    protected $signature = 'blade:lint'
        . ' {--backend=auto : One of: auto, cli, eval, ext-ast, php-parser}'
        . ' {--fast}'
        . ' {--codeclimate=false : One of: stdout, stderr, false, or a FILE to open}'
        . ' {path?*}';

    protected $description = 'Checks Blade template syntax';

    public function handle(): int
    {
        $codeclimate = $this->getCodeClimateOutput();
        $allErrors = [];

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

            $allErrors += $errors;
        }

        if ($codeclimate !== null) {
            fwrite($codeclimate, json_encode(
                array_map(function (ErrorRecord $error) {
                    return [
                        'type' => 'issue',
                        'check_name' => 'Laravel Blade Lint',
                        'description' => $error->message,
                        'categories' => ['Bug Risk'],
                        'location' => [
                            'path' => $error->path,
                            'lines' => [
                                'begin' => $error->line,
                            ],
                        ],
                        'severity' => 'blocker'
                    ];
                }, $allErrors),
                JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR
            ));
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

        $backends = [];

        switch ($this->option('backend')) {
            default:
                // fallthrough

            case 'auto':
                $fast = (bool) $this->option('fast');
                if ($fast && extension_loaded('ast')) {
                    goto ext_ast;
                } elseif ($fast && class_exists(ParserFactory::class)) {
                    goto php_parser;
                }
                goto cli;
                break;

            case 'cli':
                cli:
                $backends[] = new Backend\Cli();
                break;

            case 'eval':
                $backends[] = new Backend\Evaluate();
                break;

            case 'ext-ast':
                ext_ast:
                $backends[] = new Backend\ExtAst();
                break;

            case 'php-parser':
                php_parser:
                $backends[] = new Backend\PhpParser();
                break;
        }

        if ($this->getOutput()->isVerbose()) {
            foreach ($backends as $backend) {
                $this->info('blade-lint: Using backend: ' . $backend->name());
            }
        }

        $errors = [];

        foreach ($backends as $backend) {
            $errors += $backend->analyze($file, $compiled);
        }

        return $errors;
    }

    /**
     * @return ?resource
     */
    private function getCodeClimateOutput(): mixed
    {
        $codeclimate = $this->option('codeclimate') ?: 'stderr';
        if ($codeclimate === true || is_array($codeclimate)) {
            $codeclimate = 'stderr';
        }
        return match ($codeclimate) {
            'false' => null,
            'stderr' => STDERR,
            'stdout' => STDOUT,
            default => fopen($codeclimate, 'w') ?: null,
        };
    }
}
