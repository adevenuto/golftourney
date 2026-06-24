<?php

namespace Tests\Feature;

use App\Models\League;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\WithLeague;
use Tests\TestCase;

/**
 * Write & delete actions require league-admin rights, enforced server-side.
 * Only the denied paths (guest 401 / non-admin 403) are exercised here.
 */
class AuthorizationTest extends TestCase
{
    use RefreshDatabase, WithLeague;

    protected League $league;

    protected function setUp(): void
    {
        parent::setUp();

        // A league with a golfer (id 1) + round (id 1) so the bound URIs resolve
        // and execution reaches the `admin` middleware.
        $this->league = League::factory()->create();
        $golfer = $this->golferIn($this->league);
        $this->roundFor($golfer, $this->league);
    }

    /**
     * Admin-only routes (gated by the `admin` middleware). Round write routes are
     * NOT here — they're self-or-admin and covered by SelfServiceRoundTest.
     *
     * @return array<int, array{0:string,1:string}>
     */
    public static function adminRoutes(): array
    {
        return [
            ['post', '/golfers'],
            ['put', '/golfers/1'],
            ['delete', '/golfers/1'],
            ['post', '/golfers/1/invite'],
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
        $this->actingAs($this->playerOf($this->league))
            ->{"{$method}Json"}($uri)
            ->assertForbidden(); // 403
    }
}
