<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\AppSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        AppSetting::set('email_verification', '0');
        AppSetting::set('otp_verification', '0');
    }

    public function test_non_admin_cannot_access_user_crud(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'is_active' => true,
        ]);

        $this->actingAs($user);

        // Try viewing create page
        $response = $this->get(route('admin.users.create'));
        $response->assertStatus(403);

        // Try storing new user
        $response = $this->post(route('admin.users.store'), []);
        $response->assertStatus(403);

        // Try editing user page
        $otherUser = User::factory()->create();
        $response = $this->get(route('admin.users.edit', $otherUser));
        $response->assertStatus(403);

        // Try deleting user
        $response = $this->delete(route('admin.users.destroy', $otherUser));
        $response->assertStatus(403);
    }

    public function test_admin_can_view_create_user_form(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('admin.users.create'));
        $response->assertStatus(200);
        $response->assertSee('Add New User');
    }

    public function test_admin_can_create_user_and_auto_provisions_wallet(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        $response = $this->post(route('admin.users.store'), [
            'name'            => 'John Admincreated',
            'username'        => 'johncreated',
            'email'           => 'johncreated@example.com',
            'phone'           => '09012345678',
            'password'        => 'secret123',
            'transaction_pin' => '9999',
            'user_type'       => 'agent',
            'is_admin'        => '0',
            'is_active'       => '1',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        
        $this->assertDatabaseHas('users', [
            'username' => 'johncreated',
            'email'    => 'johncreated@example.com',
            'user_type' => 'agent',
        ]);

        $newUser = User::where('username', 'johncreated')->first();
        $this->assertNotNull($newUser->wallet);
        $this->assertEquals(0.00, $newUser->wallet->balance);
        $this->assertTrue(Hash::check('secret123', $newUser->password));
        $this->assertTrue(Hash::check('9999', $newUser->transaction_pin));
    }

    public function test_admin_can_view_edit_user_form(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $user = User::factory()->create();

        $this->actingAs($admin);

        $response = $this->get(route('admin.users.edit', $user));
        $response->assertStatus(200);
        $response->assertSee($user->name);
    }

    public function test_admin_can_update_user_details(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'name'     => 'Old Name',
            'username' => 'oldusername',
            'email'    => 'old@example.com',
        ]);

        $this->actingAs($admin);

        $response = $this->patch(route('admin.users.update', $user), [
            'name'                => 'New Name',
            'username'            => 'newusername',
            'email'               => 'new@example.com',
            'phone'               => '08099887766',
            'password'            => 'newpassword123',
            'transaction_pin'     => '8888',
            'user_type'           => 'agent',
            'is_active'           => '1',
            'is_admin'            => '0',
            '_redirect_to_index'  => '1',
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $user->refresh();
        $this->assertEquals('New Name', $user->name);
        $this->assertEquals('newusername', $user->username);
        $this->assertEquals('new@example.com', $user->email);
        $this->assertEquals('08099887766', $user->phone);
        $this->assertEquals('agent', $user->user_type);
        $this->assertTrue(Hash::check('newpassword123', $user->password));
        $this->assertTrue(Hash::check('8888', $user->transaction_pin));
    }

    public function test_admin_can_soft_delete_user(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $user = User::factory()->create();

        $this->actingAs($admin);

        $response = $this->delete(route('admin.users.destroy', $user));
        $response->assertRedirect(route('admin.users.index'));

        // Assert user is soft-deleted
        $this->assertSoftDeleted('users', [
            'id' => $user->id,
        ]);
    }

    public function test_admin_can_view_user_show_with_transaction_tables(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $wallet = \App\Models\Wallet::create([
            'user_id' => $user->id,
            'balance' => 3809.00,
            'total_funded' => 4500.00,
            'total_spent' => 691.00,
        ]);

        $walletTx = \App\Models\WalletTransaction::create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'reference' => 'df2210b7-c49d-463f-b7b3-794170650960',
            'type' => 'credit',
            'amount' => 2000.00,
            'balance_before' => 1809.00,
            'balance_after' => 3809.00,
            'description' => 'Wallet funding via Payscribe_dva',
            'status' => 'success',
        ]);

        $serviceTx = \App\Models\ServiceTransaction::create([
            'user_id' => $user->id,
            'reference' => 'AIR20261022204312345678',
            'service_type' => 'airtime',
            'recipient' => '09023000058',
            'amount' => 495.00,
            'status' => 'success',
            'provider' => 'vtpass',
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('admin.users.show', $user));
        $response->assertStatus(200);

        // Verify wallet transaction row and link
        $response->assertSee('df2210b7-c49d-');
        $response->assertSee('Wallet funding via Payscribe_dva');
        $response->assertSee(route('admin.transactions.show', ['type' => 'wallet', 'id' => $walletTx->id]));

        // Verify service transaction row and link
        $response->assertSee('09023000058');
        $response->assertSee(route('admin.transactions.show', ['type' => 'service', 'id' => $serviceTx->id]));
    }
}
