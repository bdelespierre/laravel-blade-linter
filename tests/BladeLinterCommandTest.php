<?php

namespace Bdelespierre\LaravelBladeLinter\Tests;

use Bdelespierre\LaravelBladeLinter\BladeLinterServiceProvider;
use Illuminate\Support\Facades\Artisan;
use Orchestra\Testbench\TestCase;

class BladeLinterCommandTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            BladeLinterServiceProvider::class
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app->make('config')->set('view.paths', [
            __DIR__ . '/views',
        ]);
    }

    public function testValidBladeFilePass()
    {
        $path = __DIR__ . '/views/valid.blade.php';
        $exit = Artisan::call('blade:lint', compact('path'));

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

    public function testInvalidBladeFilePass()
    {
        $path = __DIR__ . '/views/invalid.blade.php';
        $exit = Artisan::call('blade:lint', compact('path'));

        $this->assertEquals(
            1,
            $exit,
            "Validating an invalid template should exit with a 'NOK' status"
        );

        $this->assertEquals(
            "PHP Parse error:  syntax error, unexpected ')' in {$path} on line 1",
            trim(Artisan::output()),
            "Syntax error should be displayed"
        );
    }

    public function testWithoutPath()
    {
        $exit = Artisan::call('blade:lint');

        $this->assertEquals(
            1,
            $exit,
            "Validating an invalid template should exit with a 'NOK' status"
        );

        $this->assertStringStartsWith(
            "PHP Parse error:  syntax error",
            trim(Artisan::output()),
            "Syntax error should be displayed"
        );
    }

    public function testWithMultiplePaths()
    {
        $path = [
            __DIR__ . '/views/valid.blade.php',
            __DIR__ . '/views/invalid.blade.php',
        ];

        $exit = Artisan::call('blade:lint', compact('path'));

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

        $this->assertStringContainsString(
            "PHP Parse error:  syntax error, unexpected ')' in {$path[1]} on line 1",
            $output,
            "Syntax error should be displayed"
        );
    }
}
