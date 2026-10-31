<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationsApiTest extends TestCase
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

    public function test_user_can_list_notifications(): void
    {
        Notification::factory()->count(5)->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/notifications');

        $response->assertStatus(200);

        $json = $response->json();
        // Handle both paginated response (with data, links, meta) and direct array
        $data = isset($json['data']) ? $json['data'] : $json;

        $this->assertIsArray($data);
        $this->assertCount(5, $data);

        foreach ($data as $notification) {
            $this->assertArrayHasKey('id', $notification);
            $this->assertArrayHasKey('type', $notification);
            $this->assertArrayHasKey('title', $notification);
            $this->assertArrayHasKey('message', $notification);
            $this->assertArrayHasKey('read_at', $notification);
            $this->assertArrayHasKey('created_at', $notification);
            $this->assertArrayHasKey('updated_at', $notification);
        }
    }

    public function test_user_can_filter_notifications_by_read_status(): void
    {
        Notification::factory()->count(3)->create(['user_id' => $this->user->id, 'read_at' => now()]);
        Notification::factory()->count(2)->create(['user_id' => $this->user->id, 'read_at' => null]);

        // Unread notifications
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/notifications?read=false');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
        foreach ($response->json('data') as $notification) {
            $this->assertNull($notification['read_at']);
        }

        // Read notifications
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/notifications?read=true');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
        foreach ($response->json('data') as $notification) {
            $this->assertNotNull($notification['read_at']);
        }
    }

    public function test_user_can_filter_notifications_by_type(): void
    {
        Notification::factory()->create(['user_id' => $this->user->id, 'type' => 'budget_exceeded']);
        Notification::factory()->create(['user_id' => $this->user->id, 'type' => 'goal_completed']);
        Notification::factory()->create(['user_id' => $this->user->id, 'type' => 'budget_exceeded']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/notifications?type=budget_exceeded');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
        foreach ($response->json('data') as $notification) {
            $this->assertEquals('budget_exceeded', $notification['type']);
        }
    }

    public function test_user_can_get_unread_count(): void
    {
        Notification::factory()->count(3)->create(['user_id' => $this->user->id, 'read_at' => now()]);
        Notification::factory()->count(7)->create(['user_id' => $this->user->id, 'read_at' => null]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/notifications/unread-count');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'count' => 7,
            ]);
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson("/api/notifications/{$notification->id}/read");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $notification->id,
            ]);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        Notification::factory()->count(5)->create(['user_id' => $this->user->id, 'read_at' => null]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/notifications/read-all');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'All notifications marked as read',
            ]);

        $unreadCount = $this->user->notifications()->whereNull('read_at')->count();
        $this->assertEquals(0, $unreadCount);
    }

    public function test_user_can_delete_notification(): void
    {
        $notification = Notification::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->deleteJson("/api/notifications/{$notification->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Notification deleted successfully',
            ]);

        // Check soft delete
        $this->assertSoftDeleted('notifications', ['id' => $notification->id]);
    }

    public function test_unauthorized_user_cannot_access_notifications(): void
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->getJson('/api/notifications');

        $response->assertStatus(401);
    }

    public function test_user_cannot_access_other_user_notifications(): void
    {
        $otherUser = User::factory()->create();
        $notification = Notification::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson("/api/notifications/{$notification->id}/read");

        $response->assertStatus(404);
    }

    public function test_mark_read_nonexistent_notification_returns_404(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/notifications/99999/read');

        $response->assertStatus(404)
            ->assertJsonFragment([
                'message' => 'Notification not found',
            ]);
    }

    public function test_delete_nonexistent_notification_returns_404(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->deleteJson('/api/notifications/99999');

        $response->assertStatus(404)
            ->assertJsonFragment([
                'message' => 'Notification not found',
            ]);
    }

    public function test_notifications_ordered_by_created_at_desc(): void
    {
        $first = Notification::factory()->create(['user_id' => $this->user->id, 'created_at' => now()->subDays(2)]);
        $second = Notification::factory()->create(['user_id' => $this->user->id, 'created_at' => now()->subDay()]);
        $third = Notification::factory()->create(['user_id' => $this->user->id, 'created_at' => now()]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/notifications');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals($third->id, $data[0]['id']);
        $this->assertEquals($second->id, $data[1]['id']);
        $this->assertEquals($first->id, $data[2]['id']);
    }
}