<?php

namespace Tests\Feature\User;

use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_change_password_with_valid_credentials()
    {
        $user = User::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'nickname' => 'johndoe',
            'customer_number' => User::generateCustumerNumber(),
            'role_id' => 2,
            'status' => 1,
            'email' => 'john@example.com',
            'mobile' => '09123456789',
            'password' => Hash::make('Oldpassword123!'),
            'profile_photo_path' => config('image.default-profile-image'),
            'bg_photo_path' => config('image.default-background-image'),
        ]);

        Sanctum::actingAs($user);

        $response = $this->patchJson('/api/profile/users/change-password', [
            'current_password' => 'Oldpassword123!',
            'password' => 'Newpassword123!',
            'password_confirmation' => 'Newpassword123!',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 1,
                'message' => __('site.Password has been changed successfully'),
            ]);

        // Verify password was actually changed
        $this->assertTrue(Hash::check('Newpassword123!', $user->fresh()->password));
    }

    public function test_user_cannot_change_password_with_incorrect_current_password()
    {
        $user = User::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'nickname' => 'janedoe',
            'customer_number' => User::generateCustumerNumber(),
            'role_id' => 2,
            'status' => 1,
            'email' => 'jane@example.com',
            'mobile' => '09123456788',
            'password' => Hash::make('Oldpassword123!'),
            'profile_photo_path' => config('image.default-profile-image'),
            'bg_photo_path' => config('image.default-background-image'),
        ]);

        Sanctum::actingAs($user);

        $response = $this->patchJson('/api/profile/users/change-password', [
            'current_password' => 'wrongpassword',
            'password' => 'Newpassword123!',
            'password_confirmation' => 'Newpassword123!',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 0,
                'message' => __('site.Current password is incorrect'),
            ]);
    }

    public function test_user_cannot_change_password_without_confirmation()
    {
        $user = User::create([
            'first_name' => 'Bob',
            'last_name' => 'Smith',
            'nickname' => 'bobsmith',
            'customer_number' => User::generateCustumerNumber(),
            'role_id' => 2,
            'status' => 1,
            'email' => 'bob@example.com',
            'mobile' => '09123456787',
            'password' => Hash::make('Oldpassword123!'),
            'profile_photo_path' => config('image.default-profile-image'),
            'bg_photo_path' => config('image.default-background-image'),
        ]);

        Sanctum::actingAs($user);

        $response = $this->patchJson('/api/profile/users/change-password', [
            'current_password' => 'Oldpassword123!',
            'password' => 'Newpassword123!',
            'password_confirmation' => 'differentpassword',
        ]);

        $response->assertStatus(422);
    }

    public function test_guest_cannot_change_password()
    {
        $response = $this->patchJson('/api/profile/users/change-password', [
            'current_password' => 'Password123!',
            'password' => 'Newpassword123!',
            'password_confirmation' => 'Newpassword123!',
        ]);

        $response->assertStatus(401);
    }

    public function test_user_cannot_change_password_without_capital_letter()
    {
        $user = User::create([
            'first_name' => 'David',
            'last_name' => 'Wilson',
            'nickname' => 'davidwilson',
            'customer_number' => User::generateCustumerNumber(),
            'role_id' => 2,
            'status' => 1,
            'email' => 'david@example.com',
            'mobile' => '09123456784',
            'password' => Hash::make('Oldpassword123!'),
            'profile_photo_path' => config('image.default-profile-image'),
            'bg_photo_path' => config('image.default-background-image'),
        ]);

        Sanctum::actingAs($user);

        $response = $this->patchJson('/api/profile/users/change-password', [
            'current_password' => 'Oldpassword123!',
            'password' => 'newpassword123!',
            'password_confirmation' => 'newpassword123!',
        ]);

        $response->assertStatus(422);
    }

    public function test_user_cannot_change_password_without_symbol()
    {
        $user = User::create([
            'first_name' => 'Eva',
            'last_name' => 'Brown',
            'nickname' => 'evabrown',
            'customer_number' => User::generateCustumerNumber(),
            'role_id' => 2,
            'status' => 1,
            'email' => 'eva@example.com',
            'mobile' => '09123456783',
            'password' => Hash::make('Oldpassword123!'),
            'profile_photo_path' => config('image.default-profile-image'),
            'bg_photo_path' => config('image.default-background-image'),
        ]);

        Sanctum::actingAs($user);

        $response = $this->patchJson('/api/profile/users/change-password', [
            'current_password' => 'Oldpassword123!',
            'password' => 'Newpassword123',
            'password_confirmation' => 'Newpassword123',
        ]);

        $response->assertStatus(422);
    }
}
