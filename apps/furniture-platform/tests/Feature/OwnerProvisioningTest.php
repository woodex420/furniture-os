<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Support\Provisioning\ProvisionFirstOwner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class OwnerProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_owner_is_created_once_with_a_hashed_password_and_only_defined_ability(): void
    {
        $owner = app(ProvisionFirstOwner::class)->provision(' Synthetic Owner ', ' OWNER@example.test ', 'synthetic-password');

        $this->assertSame('Synthetic Owner', $owner->name);
        $this->assertSame('owner@example.test', $owner->email);
        $this->assertTrue($owner->is_owner);
        $this->assertNotSame('synthetic-password', $owner->password);
        $this->assertTrue(Hash::check('synthetic-password', $owner->password));
        $this->assertTrue(Gate::forUser($owner)->allows('workspace.view'));
        $this->assertFalse(Gate::forUser($owner)->allows('users.manage'));
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('owner_initializations', 1);

        try {
            app(ProvisionFirstOwner::class)->provision('Other', 'other@example.test', 'different-password');
            $this->fail('The singleton must prevent a second owner.');
        } catch (RuntimeException) {
            $this->assertDatabaseCount('users', 1);
            $this->assertDatabaseCount('owner_initializations', 1);
        }
    }

    public function test_existing_user_blocks_initialization_and_does_not_leave_a_marker(): void
    {
        User::factory()->create();
        try {
            app(ProvisionFirstOwner::class)->provision('Owner', 'owner@example.test', 'synthetic-password');
            $this->fail('Existing installations must be preserved.');
        } catch (RuntimeException) {
            $this->assertDatabaseCount('users', 1);
            $this->assertDatabaseCount('owner_initializations', 0);
        }
    }

    public function test_invalid_details_and_overlong_bcrypt_password_are_rejected_without_writes(): void
    {
        foreach ([['', 'owner@example.test', 'synthetic-password'], ['Owner', 'invalid-email', 'synthetic-password'], ['Owner', 'owner@example.test', 'short'], ['Owner', 'owner@example.test', str_repeat('a', 73)]] as [$name, $email, $password]) {
            try {
                app(ProvisionFirstOwner::class)->provision($name, $email, $password);
                $this->fail('Invalid details must not create an account.');
            } catch (ValidationException) {
                $this->assertDatabaseCount('users', 0);
                $this->assertDatabaseCount('owner_initializations', 0);
            }
        }
    }

    public function test_non_owner_cannot_open_workspace_and_cannot_mass_assign_owner_flag(): void
    {
        $user = User::factory()->create(['is_owner' => false]);
        $user->fill(['is_owner' => true]);
        $user->save();
        $this->assertFalse($user->refresh()->is_owner);
        $this->actingAs($user)->get('/admin')->assertForbidden()
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->assertFalse(Gate::forUser($user)->allows('workspace.view'));
    }

    public function test_command_refuses_noninteractive_provisioning_without_writes(): void
    {
        $this->artisan('furniture:provision-owner', ['--no-interaction' => true])
            ->assertFailed();
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('owner_initializations', 0);
    }
}
