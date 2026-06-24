<?php

namespace Tests\Feature;

use App\Models\League;
use App\Models\User;
use App\Notifications\PlayerInvitation;
use Illuminate\Contracts\Mail\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\Concerns\WithLeague;
use Tests\TestCase;

class InviteTest extends TestCase
{
    use RefreshDatabase, WithLeague;

    public function test_admin_can_invite_a_player_who_has_a_real_email(): void
    {
        Notification::fake();
        $league = League::factory()->create();
        $player = $this->golferIn($league, ['email' => 'player@example.com']); // login-less, real email

        $this->actingAs($this->adminOf($league))
            ->post(route('golfers.invite', $player))
            ->assertRedirect();

        Notification::assertSentTo($player, PlayerInvitation::class);
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
            ->post(route('golfers.invite', $player))
            ->assertRedirect()           // not a 500
            ->assertSessionHas('invite_link'); // copyable fallback still provided
    }

    public function test_cannot_invite_a_player_without_a_real_email(): void
    {
        $league = League::factory()->create();
        $player = $this->golferIn($league, ['email' => null]);

        $this->actingAs($this->adminOf($league))
            ->postJson(route('golfers.invite', $player))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['invite']);
    }

    public function test_cannot_invite_a_player_who_already_logs_in(): void
    {
        $league = League::factory()->create();
        $member = $this->playerOf($league); // factory user with a password

        $this->actingAs($this->adminOf($league))
            ->postJson(route('golfers.invite', $member))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['invite']);
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
