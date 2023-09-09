<?php
namespace Ymigval\LaravelModelToDatatables\Tests;

use Orchestra\Testbench\Database\MigrateProcessor;
use Orchestra\Testbench\TestCase as TestbenchTestCase;
use Workbench\Database\Seeders\DatabaseSeeder;
use Ymigval\LaravelModelToDatatables\DataTablesServiceProvider;

class TestCase extends TestbenchTestCase
{
    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        (new MigrateProcessor($this, $this->resolvePackageMigrationsOptions(__DIR__ . '/../workbench/database/migrations')))->rollback();

        $this->loadMigrationsFrom(__DIR__ . '/../workbench/database/migrations');

        $this->seed(DatabaseSeeder::class);

        $this->call('
            /test-tabla',
            'GET',
            json_decode(file_get_contents(__DIR__ . '/test_sent_parameters_datatables.json'), true)
        );
    }

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app)
    {
        $packageProviders = parent::getPackageProviders($app);

        $myPackageProviders = [DataTablesServiceProvider::class];

        return array_merge($packageProviders, $myPackageProviders);
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app)
    {
        // Define environment.
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', __DIR__ . '/../workbench/database/database.sqlite');
    }
}
