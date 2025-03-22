<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\User;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
    ];

    public function boot(): void
    {
        Gate::define('manage-users', function (User $user) {
            return true; // Temporarily allow all users
        });

        Gate::define('manage-warehouses', function (User $user) {
            return true; // Temporarily allow all users
        });

        Gate::define('manage-stores', function (User $user) {
            return true;
        });

        Gate::define('manage-devices', function (User $user) {
            return true;
        });

        Gate::define('manage-transfers', function (User $user) {
            return true;
        });

        Gate::define('view-transfers', function (User $user) {
            return true;
        });

        Gate::define('receive-transfers', function (User $user) {
            return true;
        });

        Gate::define('manage-sales', function (User $user) {
            return true;
        });

        Gate::define('manage-emis', function (User $user) {
            return true;
        });

        Gate::define('close-emis', function (User $user) {
            return true;
        });

        Gate::define('create-demands', function (User $user) {
            return true;
        });

        Gate::define('process-demands', function (User $user) {
            return true;
        });

        Gate::define('generate-reports', function (User $user) {
            return true;
        });
    }
}
