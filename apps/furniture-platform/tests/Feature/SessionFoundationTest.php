<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SessionFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_sent_to_sign_in_and_cannot_log_out(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
        $this->post('/logout')->assertRedirect(route('login'));
        $this->get('/login')->assertOk()->assertSee('Sign in')->assertSee('name="_token"', false)
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_sign_in_requires_valid_existing_credentials(): void
    {
        User::factory()->create(['email' => 'person@example.test', 'password' => Hash::make('synthetic-password'), 'is_owner' => true]);
        $this->post('/login', ['email' => 'person@example.test', 'password' => 'incorrect'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/login', ['email' => 'person@example.test', 'password' => 'synthetic-password'])
            ->assertRedirect('/admin');
        $this->assertAuthenticated();
        $this->get('/admin')->assertStatus(503)->assertSee('integration is pending')
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_validation_and_login_throttling(): void
    {
        $this->post('/login', [])->assertSessionHasErrors(['email', 'password']);
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'nobody@example.test', 'password' => 'wrong'])->assertSessionHasErrors('email');
        }
        // Email case must not create a fresh account/IP bucket.
        $this->post('/login', ['email' => 'NOBODY@example.test', 'password' => 'wrong'])->assertStatus(429);
        $this->assertGuest();
    }

    public function test_invalid_login_does_not_reveal_account_existence_or_flash_password(): void
    {
        User::factory()->create(['email' => 'person@example.test', 'password' => Hash::make('synthetic-password')]);

        $this->post('/login', ['email' => 'person@example.test', 'password' => 'wrong'])
            ->assertSessionHasErrors(['email' => 'The provided credentials are incorrect.'])
            ->assertSessionMissing('_old_input.password');
        $this->post('/login', ['email' => 'nobody@example.test', 'password' => 'wrong'])
            ->assertSessionHasErrors(['email' => 'The provided credentials are incorrect.'])
            ->assertSessionMissing('_old_input.password');
    }

    public function test_login_rotates_the_session_identifier(): void
    {
        User::factory()->create(['email' => 'person@example.test', 'password' => Hash::make('synthetic-password')]);
        $this->get('/login')->assertOk();
        $before = $this->app['session']->driver()->getId();

        $this->post('/login', ['email' => 'person@example.test', 'password' => 'synthetic-password'])
            ->assertRedirect('/admin');
        $this->assertAuthenticated();
        $this->assertNotSame($before, $this->app['session']->driver()->getId());
    }

    public function test_login_limits_many_different_accounts_from_the_same_ip(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->post('/login', ['email' => "person{$i}@example.test", 'password' => 'wrong'])
                ->assertSessionHasErrors('email');
        }
        $this->post('/login', ['email' => 'another@example.test', 'password' => 'wrong'])->assertStatus(429);
        $this->assertGuest();
    }

    public function test_malformed_email_values_are_validated_not_crashed(): void
    {
        $this->post('/login', ['email' => ['unexpected'], 'password' => 'wrong'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_logout_clears_the_session_and_protected_page_is_no_longer_accessible(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_public_registration_is_not_available(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', ['email' => 'anyone@example.test'])->assertNotFound();
    }

    public function test_default_seeder_creates_no_users(): void
    {
        $this->seed();
        $this->assertDatabaseCount('users', 0);
    }
}
