<?php

namespace Tests;

use Bdelespierre\LaravelBladeLinter\BladeLinterServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Artisan;
use Orchestra\Testbench\TestCase;

class BladeLinterCommandTest extends TestCase
{
    /**
     * @param Application $app
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            BladeLinterServiceProvider::class
        ];
    }

    /**
     * @param Application $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app->make('config')->set('view.paths', [
            __DIR__ . '/views',
        ]);
    }

    public function testValidBladeFilePass(): void
    {
        $path = __DIR__ . '/views/valid.blade.php';
        $exit = Artisan::call('blade:lint', ['-v' => true, 'path' => $path]);

        $this->assertEquals(
            0,
            $exit,
            "Validating a valid template should exit with an 'OK' status"
        );

        $this->assertEquals(
            "No syntax errors detected in {$path}",
            trim(Artisan::output()),
            "Validating a valid template should display the validation message"
        );
    }

    public function testInvalidBladeFilePass(): void
    {
        $path = __DIR__ . '/views/invalid.blade.php';
        $exit = Artisan::call('blade:lint', ['-v' => true, 'path' => $path]);

        $this->assertEquals(
            1,
            $exit,
            "Validating an invalid template should exit with a 'NOK' status"
        );

        $this->assertMatchesRegularExpression(
            "~Parse error:  ?syntax error, unexpected .* in {$path} on line 1~",
            trim(Artisan::output()),
            "Syntax error should be displayed"
        );
    }

    public function testWithoutPath(): void
    {
        $exit = Artisan::call('blade:lint', ['-v' => true]);

        $this->assertEquals(
            1,
            $exit,
            "Validating an invalid template should exit with a 'NOK' status"
        );

        $output = Artisan::output();

        $this->assertMatchesRegularExpression(
            "~No syntax errors detected in .*/tests/views/invalid-phpstan\\.blade\\.php\n~",
            $output,
        );

        $this->assertMatchesRegularExpression(
            "~Parse error:  ?syntax error, unexpected .* in .*/tests/views/invalid\\.blade\\.php on line 1\n~",
            $output,
        );

        $this->assertMatchesRegularExpression(
            "~No syntax errors detected in .*/tests/views/valid\\.blade\\.php\n~",
            $output,
        );
    }

    public function testWithMultiplePaths(): void
    {
        $path = [
            __DIR__ . '/views/valid.blade.php',
            __DIR__ . '/views/invalid.blade.php',
        ];

        $exit = Artisan::call('blade:lint', ['-v' => true, 'path' => $path]);

        $this->assertEquals(
            1,
            $exit,
            "Validating an invalid template should exit with a 'NOK' status"
        );

        $output = trim(Artisan::output());

        $this->assertStringContainsString(
            "No syntax errors detected in {$path[0]}",
            $output,
            "Validating a valid template should display the validation message"
        );

        $this->assertMatchesRegularExpression(
            "~Parse error:  ?syntax error, unexpected .* in {$path[1]} on line 1~",
            $output,
            "Syntax error should be displayed"
        );
    }

    public function testWithPhpStan(): void
    {
        $path = [
            __DIR__ . '/views/invalid-phpstan.blade.php',
        ];

        $exit = Artisan::call('blade:lint', [
            '--phpstan' => 'vendor/bin/phpstan',
            'path' => $path,
        ]);

        $this->assertEquals(
            1,
            $exit,
            "Validating an invalid template for PHPStan should exit with a 'NOK' status"
        );

        $this->assertMatchesRegularExpression(
            "~PHPStan error:  access to constant SOME_CONSTANT on an unknown class SomeClass " .
            "in .*/tests/views/invalid-phpstan\\.blade\\.php on line 4\n~",
            Artisan::output(),
        );
    }

    public function testInvalidPHPStan(): void
    {
        $this->expectException(\RuntimeException::class);

        $path = [
            __DIR__ . '/views/invalid-phpstan.blade.php',
        ];

        $exit = Artisan::call('blade:lint', [
            '--phpstan' => 'invalid/path/to/phpstan',
            'path' => $path,
        ]);
    }
}
