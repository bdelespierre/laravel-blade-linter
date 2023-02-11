<?php
declare(strict_types=1);

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

    /**
     * @dataProvider backendProvider
     */
    public function testValidBladeFilePass(string $backend): void
    {
        $path = __DIR__ . '/views/valid.blade.php';
        $exit = Artisan::call('blade:lint', ['-v' => true, '--backend' => $backend, 'path' => $path]);

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

    /**
     * @dataProvider backendProvider
     */
    public function testInvalidBladeFilePass(string $backend): void
    {
        $path = __DIR__ . '/views/invalid.blade.php';
        $exit = Artisan::call('blade:lint', ['-v' => true, '--backend' => $backend, 'path' => $path]);

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

    /**
     * @dataProvider backendProvider
     */
    public function testWithoutPath(string $backend): void
    {
        $exit = Artisan::call('blade:lint', ['-v' => true, '--backend' => $backend]);

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

    /**
     * @dataProvider backendProvider
     */
    public function testWithMultiplePaths(string $backend): void
    {
        $path = [
            __DIR__ . '/views/valid.blade.php',
            __DIR__ . '/views/invalid.blade.php',
        ];

        $exit = Artisan::call('blade:lint', ['-v' => true, '--backend' => $backend, 'path' => $path]);

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

    public function backendProvider(): array
    {
        return [
            ['auto'],
            ['cli'],
            ['ext-ast'],
        ];
    }
}
