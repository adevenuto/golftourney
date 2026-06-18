<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Tests\TestCase;

/**
 * Verifies that write & delete actions are enforced server-side.
 *
 * These tests intentionally only exercise the *denied* paths (guest / non-admin),
 * which short-circuit in middleware before any database write occurs. This keeps
 * them safe to run against a non-isolated database until a dedicated test DB is
 * configured (Phase 2/3).
 */
class AuthorizationTest extends TestCase
{
    /** @return array<int, array{0:string,1:string}> Admin-guarded [method, uri] routes. */
    public static function adminRoutes(): array
    {
        return [
            ['post', '/create/golfer'],
            ['post', '/golfers/1/edit'],
            ['delete', '/golfers/1'],
            ['post', '/rounds/store'],
            ['post', '/rounds/edit'],
            ['delete', '/rounds/1'],
        ];
    }

    /**
     * @dataProvider adminRoutes
     */
    public function test_guests_cannot_access_admin_routes(string $method, string $uri): void
    {
        $this->{"{$method}Json"}($uri)->assertUnauthorized(); // 401
    }

    /**
     * @dataProvider adminRoutes
     */
    public function test_non_admins_cannot_access_admin_routes(string $method, string $uri): void
    {
        $player = new User(['role' => Role::Player->value]);

        $this->actingAs($player)
            ->{"{$method}Json"}($uri)
            ->assertForbidden(); // 403
    }
}
