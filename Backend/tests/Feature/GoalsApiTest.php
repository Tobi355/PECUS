<?php

namespace Tests\Feature;

use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoalsApiTest extends TestCase
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

    public function test_user_can_create_goal(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/goals', [
            'name' => 'Viaje a Europa',
            'target_amount' => 500000,
            'currency' => 'ARS',
            'target_date' => '2026-12-31',
            'current_amount' => 50000,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'name',
                'target_amount',
                'currency',
                'target_date',
                'current_amount',
                'status',
                'progress',
                'remaining',
                'created_at',
                'updated_at',
            ])
            ->assertJsonFragment([
                'name' => 'Viaje a Europa',
                'target_amount' => '500000.00',
                'currency' => 'ARS',
            ]);

        $this->assertDatabaseHas('goals', [
            'user_id' => $this->user->id,
            'name' => 'Viaje a Europa',
            'target_amount' => 500000,
        ]);
    }

    public function test_user_can_list_goals(): void
    {
        Goal::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/goals');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'name',
                    'target_amount',
                    'currency',
                    'target_date',
                    'current_amount',
                    'status',
                    'progress',
                    'remaining',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertCount(3, $response->json());
    }

    public function test_user_can_view_single_goal(): void
    {
        $goal = Goal::factory()->create(['user_id' => $this->user->id, 'name' => 'Meta de prueba']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson("/api/goals/{$goal->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $goal->id,
                'name' => 'Meta de prueba',
            ]);
    }

    public function test_user_can_update_goal(): void
    {
        $goal = Goal::factory()->create(['user_id' => $this->user->id, 'name' => 'Meta original', 'current_amount' => 10000]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->putJson("/api/goals/{$goal->id}", [
            'name' => 'Meta actualizada',
            'current_amount' => 25000,
            'status' => 'COMPLETED',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Meta actualizada',
                'current_amount' => '25000.00',
                'status' => 'COMPLETED',
            ]);

        $this->assertDatabaseHas('goals', [
            'id' => $goal->id,
            'name' => 'Meta actualizada',
            'current_amount' => 25000,
            'status' => 'COMPLETED',
        ]);
    }

    public function test_user_can_delete_goal(): void
    {
        $goal = Goal::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->deleteJson("/api/goals/{$goal->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Goal deleted successfully',
            ]);

        // Check soft delete
        $this->assertSoftDeleted('goals', ['id' => $goal->id]);
    }

    public function test_goal_progress_calculation(): void
    {
        $goal = Goal::factory()->create([
            'user_id' => $this->user->id,
            'target_amount' => 100000,
            'current_amount' => 50000,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson("/api/goals/{$goal->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'progress' => 50,
                'remaining' => 50000,
            ]);
    }

    public function test_goal_exceeds_target_progress(): void
    {
        $goal = Goal::factory()->create([
            'user_id' => $this->user->id,
            'target_amount' => 100000,
            'current_amount' => 120000,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson("/api/goals/{$goal->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'progress' => 120,
                'remaining' => 0,
            ]);
    }

    public function test_unauthorized_user_cannot_access_goals(): void
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->getJson('/api/goals');

        $response->assertStatus(401);
    }

    public function test_user_cannot_access_other_user_goals(): void
    {
        $otherUser = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson("/api/goals/{$goal->id}");

        $response->assertStatus(404);
    }

    public function test_create_goal_validation_errors(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/goals', [
            'name' => '',
            'target_amount' => -100,
            'currency' => 'INVALID',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'target_amount', 'currency']);
    }
}