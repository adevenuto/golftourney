<?php

namespace Tests\Feature;

use App\Models\League;
use App\Models\User;
use App\Notifications\PlayerInvitation;
use Illuminate\Contracts\Mail\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\Concerns\WithLeague;
use Tests\TestCase;

class InviteTest extends TestCase
{
    use RefreshDatabase, WithLeague;

    public function test_admin_can_invite_a_player_with_a_confirmed_email(): void
    {
        Notification::fake();
        $league = League::factory()->create();
        $player = $this->golferIn($league, ['email' => 'player@example.com']); // login-less

        $this->actingAs($this->adminOf($league))
            ->post(route('golfers.invite', $player), ['email' => 'player@example.com'])
            ->assertRedirect()
            ->assertSessionHas('invite_link');

        Notification::assertSentTo($player, PlayerInvitation::class);

        // The invite is timestamped, so the roster can show "Invited …" + resend.
        $this->assertNotNull($player->fresh()->invited_at);
    }

    public function test_inviting_updates_the_players_email(): void
    {
        Notification::fake();
        $league = League::factory()->create();
        $player = $this->golferIn($league, ['email' => 'old@example.com']);

        $this->actingAs($this->adminOf($league))
            ->post(route('golfers.invite', $player), ['email' => 'new@example.com'])
            ->assertRedirect();

        $this->assertSame('new@example.com', $player->fresh()->email);
    }

    public function test_invite_still_returns_the_link_when_email_delivery_fails(): void
    {
        $league = League::factory()->create();
        $player = $this->golferIn($league, ['email' => 'player@example.com']);

        // Simulate a misconfigured/unreachable mailer: resolving it blows up.
        $this->app->instance(Factory::class, new class implements Factory
        {
            public function mailer($name = null)
            {
                throw new \RuntimeException('mail down');
            }
        });

        $this->actingAs($this->adminOf($league))
            ->post(route('golfers.invite', $player), ['email' => 'player@example.com'])
            ->assertRedirect()           // not a 500
            ->assertSessionHas('invite_link'); // copyable fallback still provided
    }

    public function test_inviting_requires_an_email(): void
    {
        $league = League::factory()->create();
        $player = $this->golferIn($league, ['email' => null]);

        $this->actingAs($this->adminOf($league))
            ->postJson(route('golfers.invite', $player), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_cannot_invite_with_an_email_already_in_use(): void
    {
        $league = League::factory()->create();
        $player = $this->golferIn($league, ['email' => 'player@example.com']);
        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($this->adminOf($league))
            ->postJson(route('golfers.invite', $player), ['email' => 'TAKEN@example.com']) // case-insensitive
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_cannot_invite_a_noreply_placeholder_email(): void
    {
        $league = League::factory()->create();
        $player = $this->golferIn($league, ['email' => 'player@noreply.com']);

        $this->actingAs($this->adminOf($league))
            ->postJson(route('golfers.invite', $player), ['email' => 'player@noreply.com'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_cannot_invite_a_player_who_already_logs_in(): void
    {
        $league = League::factory()->create();
        $member = $this->playerOf($league); // factory user with a password

        $this->actingAs($this->adminOf($league))
            ->postJson(route('golfers.invite', $member), ['email' => 'x@example.com'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_accepting_an_invite_sets_the_password_and_logs_in(): void
    {
        $league = League::factory()->create();
        $player = $this->golferIn($league, ['email' => 'player@example.com']);
        $token = Password::broker('invites')->createToken($player);

        $this->post(route('invite.store'), [
            'token' => $token,
            'email' => 'player@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertRedirect(route('my-handicap'));

        $this->assertTrue($player->fresh()->canLogin());
        $this->assertAuthenticatedAs($player->fresh());
    }

    public function test_accepting_an_invite_refreshes_the_roster_and_clears_the_marker(): void
    {
        $league = League::factory()->create();
        $player = $this->golferIn($league, ['email' => 'player@example.com']);
        $player->update(['invited_at' => now()]);
        $token = Password::broker('invites')->createToken($player);

        // A stale roster cache from before they logged in.
        Cache::forever($league->rosterCacheKey(), ['stale']);

        $this->post(route('invite.store'), [
            'token' => $token,
            'email' => 'player@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertRedirect(route('my-handicap'));

        // Invite marker cleared and the roster cache busted, so the admin now
        // sees an active login instead of "Invite sent".
        $this->assertNull($player->fresh()->invited_at);
        $this->assertFalse(Cache::has($league->rosterCacheKey()));
    }

    public function test_accept_rejects_a_bad_token(): void
    {
        $league = League::factory()->create();
        $player = $this->golferIn($league, ['email' => 'player@example.com']);

        $this->from(route('invite.accept', ['token' => 'x']))
            ->post(route('invite.store'), [
                'token' => 'wrong-token',
                'email' => 'player@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ])
            ->assertSessionHasErrors('email');

        $this->assertFalse($player->fresh()->canLogin());
        $this->assertGuest();
    }
}
