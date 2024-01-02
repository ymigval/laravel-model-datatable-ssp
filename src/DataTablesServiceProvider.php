<?php

namespace Ymigval\LaravelModelToDatatables;

use Illuminate\Database\Eloquent\Builder as Eloquent;
use Illuminate\Database\Query\Builder as Query;
use Illuminate\Support\ServiceProvider;

class DataTablesServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        // Bind the 'Extension' class to a closure for use in the application.
        $this->app->bind('Extension', function () {
            return new Extension();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        // Extend the Eloquent and Query builder classes with a 'datatable' macro
        // provided by the 'Extension' class, enabling DataTables functionality.
        Eloquent::macro('datatable', $this->app->make('Extension')());
        Query::macro('datatable', $this->app->make('Extension')());
    }
}
