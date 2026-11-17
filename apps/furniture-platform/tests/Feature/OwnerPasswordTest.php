<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OwnerPasswordTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::factory()->create(['is_owner' => true, 'password' => Hash::make('original-password-123')]);
    }

    public function test_account_security_requires_an_authenticated_owner(): void
    {
        $this->get('/admin/account/password')->assertRedirect('/login');
        $this->post('/admin/account/password')->assertRedirect('/login');
        $nonOwner = User::factory()->create(['is_owner' => false]);
        $this->actingAs($nonOwner)->get('/admin/account/password')->assertForbidden();
        $this->actingAs($nonOwner)->post('/admin/account/password', [])->assertForbidden();
        $this->post('/logout')->assertRedirect('/login');
        $owner = $this->owner();
        $this->actingAs($owner)->withoutVite()->get('/admin/account/password')
            ->assertOk()->assertSee('Change password')->assertSee('name="_token"', false)
            ->assertHeader('Cache-Control', 'no-store, private')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_owner_can_change_password_and_current_session_remains_valid(): void
    {
        $owner = $this->owner();
        $this->actingAs($owner)->withoutVite()->get('/admin/account/password')->assertOk();
        $oldSession = session()->getId();
        $oldToken = session()->token();
        $oldRemember = $owner->remember_token;
        $this->post('/admin/account/password', [
            'current_password' => 'original-password-123',
            'password' => 'different-password-456',
            'password_confirmation' => 'different-password-456',
        ])->assertRedirect('/admin/account/password')->assertSessionHas('status');
        $owner->refresh();
        $this->assertTrue(Hash::check('different-password-456', $owner->password));
        $this->assertFalse(Hash::check('original-password-123', $owner->password));
        $this->assertNotSame($oldRemember, $owner->remember_token);
        $this->assertNotSame($oldSession, session()->getId());
        $this->assertNotSame($oldToken, session()->token());
        $this->withoutVite()->get('/admin')->assertOk();
        $this->post('/logout')->assertRedirect('/login');
        $this->post('/login', ['email' => $owner->email, 'password' => 'original-password-123'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post('/login', ['email' => $owner->email, 'password' => 'different-password-456'])->assertRedirect('/admin');
        $this->assertAuthenticated();
    }

    public function test_invalid_inputs_never_change_password_or_flash_secrets(): void
    {
        $owner = $this->owner();
        $oldHash = $owner->password;
        $this->actingAs($owner)->from('/admin/account/password')->post('/admin/account/password', [
            'current_password' => 'wrong', 'password' => 'different-password-456', 'password_confirmation' => 'different-password-456',
        ])->assertSessionHasErrors('current_password')->assertSessionMissing('_old_input.current_password')
            ->assertSessionMissing('_old_input.password')->assertSessionMissing('_old_input.password_confirmation');
        $this->post('/admin/account/password', [
            'current_password' => 'original-password-123', 'password' => 'too-short', 'password_confirmation' => 'too-short',
        ])->assertSessionHasErrors('password');
        $this->post('/admin/account/password', [
            'current_password' => 'original-password-123', 'password' => 'different-password-456', 'password_confirmation' => 'not-the-same',
        ])->assertSessionHasErrors('password');
        $this->post('/admin/account/password', [
            'current_password' => 'original-password-123', 'password' => str_repeat('é', 37), 'password_confirmation' => str_repeat('é', 37),
        ])->assertSessionHasErrors('password');
        $this->post('/admin/account/password', [
            'current_password' => 'original-password-123', 'password' => 'original-password-123', 'password_confirmation' => 'original-password-123',
        ])->assertSessionHasErrors('password');
        $this->assertSame($oldHash, $owner->fresh()->password);
        $this->post('/admin/account/password', [])->assertStatus(429);
    }

    public function test_malformed_password_values_are_rejected_without_server_error(): void
    {
        $owner = $this->owner();
        $this->actingAs($owner)->post('/admin/account/password', [
            'current_password' => ['unexpected'], 'password' => ['unexpected'], 'password_confirmation' => ['unexpected'],
        ])->assertSessionHasErrors(['current_password', 'password']);
        $this->assertTrue(Hash::check('original-password-123', $owner->fresh()->password));
    }

    public function test_existing_password_bound_session_is_rejected_after_password_change(): void
    {
        $owner = $this->owner();
        $this->actingAs($owner)->withoutVite()->get('/admin')->assertOk();
        $oldSessionHash = session()->get('password_hash_web');
        $this->assertNotEmpty($oldSessionHash);
        $owner->forceFill(['password' => 'different-password-456'])->save();
        // Emulate a second previously-bound session returning with its old hash.
        $this->withSession(['password_hash_web' => $oldSessionHash])->get('/admin')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_login_establishes_a_password_bound_session(): void
    {
        $owner = $this->owner();
        $this->post('/login', ['email' => $owner->email, 'password' => 'original-password-123'])->assertRedirect('/admin');
        $hash = session()->get('password_hash_web');
        $this->assertIsString($hash);
        $this->assertNotSame($owner->password, $hash);
        $this->assertSame(Auth::guard()->hashPasswordForCookie($owner->password), $hash);
    }
}
