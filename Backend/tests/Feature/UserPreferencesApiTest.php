<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPreferencesApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test')->plainTextToken;
    }

    public function test_user_can_get_preferences(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/preferences');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'user_id',
                'settings',
                'created_at',
                'updated_at',
            ]);

        // Default empty settings
        $this->assertEquals([], $response->json('settings'));
    }

    public function test_user_can_update_preferences(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->putJson('/api/preferences', [
            'settings' => [
                'push_notifications' => true,
                'email_notifications' => false,
                'dark_mode' => true,
                'default_currency' => 'USD',
                'auto_login' => false,
                'auto_sync' => true,
                'backups' => true,
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'push_notifications' => true,
                'email_notifications' => false,
                'dark_mode' => true,
                'default_currency' => 'USD',
                'auto_login' => false,
                'auto_sync' => true,
                'backups' => true,
            ]);

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $this->user->id,
        ]);

        $preferences = UserPreference::where('user_id', $this->user->id)->first();
        $this->assertEquals(true, $preferences->settings['push_notifications']);
        $this->assertEquals('USD', $preferences->settings['default_currency']);
    }

    public function test_user_can_partially_update_preferences(): void
    {
        // First set some preferences
        $this->user->preferences()->create([
            'settings' => [
                'push_notifications' => true,
                'email_notifications' => true,
                'dark_mode' => false,
                'default_currency' => 'ARS',
            ],
        ]);

        // Update only one setting
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->putJson('/api/preferences', [
            'settings' => [
                'dark_mode' => true,
            ],
        ]);

        $response->assertStatus(200);

        $preferences = $this->user->preferences()->first();
        // Other settings should be preserved
        $this->assertEquals(true, $preferences->settings['push_notifications']);
        $this->assertEquals(true, $preferences->settings['email_notifications']);
        // Updated setting should be changed
        $this->assertEquals(true, $preferences->settings['dark_mode']);
        $this->assertEquals('ARS', $preferences->settings['default_currency']);
    }

    public function test_user_can_update_preferences_to_empty(): void
    {
        $this->user->preferences()->create([
            'settings' => [
                'push_notifications' => true,
            ],
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->putJson('/api/preferences', [
            'settings' => [],
        ]);

        $response->assertStatus(200);
        // Merging with empty array preserves existing settings
        $this->assertEquals(['push_notifications' => true], $response->json('settings'));
    }

    public function test_unauthorized_user_cannot_access_preferences(): void
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->getJson('/api/preferences');

        $response->assertStatus(401);
    }

    public function test_preferences_are_user_specific(): void
    {
        $otherUser = User::factory()->create();
        $otherUser->preferences()->create([
            'settings' => [
                'dark_mode' => true,
            ],
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/preferences');

        $response->assertStatus(200);
        // Should not see other user's preferences
        $this->assertNotEquals(true, $response->json('settings.dark_mode') ?? false);
    }

    public function test_settings_validation_allows_any_key_value(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->putJson('/api/preferences', [
            'settings' => [
                'custom_setting_1' => 'custom_value',
                'custom_setting_2' => 123,
                'custom_setting_3' => ['array', 'values'],
                'custom_setting_4' => true,
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'custom_setting_1' => 'custom_value',
                'custom_setting_2' => 123,
                'custom_setting_3' => ['array', 'values'],
                'custom_setting_4' => true,
            ]);
    }

    public function test_settings_validation_rejects_non_array(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->putJson('/api/preferences', [
            'settings' => 'not-an-array',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['settings']);
    }
}