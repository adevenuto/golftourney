<?php

namespace Tests\Feature;

use App\Models\Golfer;
use App\Models\Round;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Verifies that write & delete actions are enforced server-side.
 */
class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Records with id 1 so route-model-bound URIs resolve to a real model
        // and execution reaches the `admin` middleware.
        $golfer = Golfer::factory()->create();
        Round::factory()->for($golfer)->create();
    }

    /** @return array<int, array{0:string,1:string}> Admin-guarded [method, uri] routes. */
    public static function adminRoutes(): array
    {
        return [
            ['post', '/golfers'],
            ['put', '/golfers/1'],
            ['delete', '/golfers/1'],
            ['post', '/golfers/1/rounds'],
            ['put', '/rounds/1'],
            ['delete', '/rounds/1'],
        ];
    }

    #[DataProvider('adminRoutes')]
    public function test_guests_cannot_access_admin_routes(string $method, string $uri): void
    {
        $this->{"{$method}Json"}($uri)->assertUnauthorized(); // 401
    }

    #[DataProvider('adminRoutes')]
    public function test_non_admins_cannot_access_admin_routes(string $method, string $uri): void
    {
        $player = User::factory()->create(); // defaults to the player role

        $this->actingAs($player)
            ->{"{$method}Json"}($uri)
            ->assertForbidden(); // 403
    }
}
