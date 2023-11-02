<?php

namespace App\Providers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blueprint::macro('createUpdateDeleteUserId', function () {
            /** @var Blueprint $this */
            $this->foreignId('created_by')->nullable()->comment('Record creation date')->constrained('users')->onDelete('restrict');
            $this->foreignId('updated_by')->nullable()->comment('Record update date')->constrained('users')->onDelete('restrict');
            $this->foreignId('deleted_by')->nullable()->comment('Record deletion date')->constrained('users')->onDelete('restrict');
        });
    }
}
