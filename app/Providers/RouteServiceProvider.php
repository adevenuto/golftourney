<?php

namespace App\Providers;

/**
 * Temporary holder for the HOME constant referenced by the legacy laravel/ui
 * auth controllers. Removed together with laravel/ui when Breeze takes over
 * authentication. Routing itself is configured in bootstrap/app.php.
 */
class RouteServiceProvider
{
    /**
     * The path authenticated users are redirected to.
     */
    public const HOME = '/golfers';
}
