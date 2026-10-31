<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Goal;
use App\Models\Movement;
use App\Models\Notification;
use App\Models\Source;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;
    protected Source $source;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test')->plainTextToken;

        $this->source = Source::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'BANK',
            'currency' => 'ARS',
            'initial_balance' => 100000,
            'status' => 'ACTIVE',
        ]);
    }

    public function test_dashboard_returns_summary(): void
    {
        // Create some movements
        Movement::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'INCOME',
            'amount' => 50000,
            'currency' => 'ARS',
            'source_id' => $this->source->id,
            'operation_date' => now(),
            'status' => 'CONFIRMED',
        ]);

        Movement::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'EXPENSE',
            'amount' => 15000,
            'currency' => 'ARS',
            'source_id' => $this->source->id,
            'operation_date' => now(),
            'status' => 'CONFIRMED',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'summary' => [
                    'income',
                    'expenses',
                    'balance',
                    'available',
                ],
                'sources',
                'category_distribution',
                'recent_movements',
            ]);

        $this->assertEquals(50000, $response->json('summary.income'));
        $this->assertEquals(15000, $response->json('summary.expenses'));
        $this->assertEquals(35000, $response->json('summary.balance'));
        $this->assertEquals(100000 + 35000, $response->json('summary.available'));
    }

    public function test_dashboard_returns_sources_with_balances(): void
    {
        $source2 = Source::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'CASH',
            'currency' => 'ARS',
            'initial_balance' => 5000,
            'status' => 'ACTIVE',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/dashboard');

        $response->assertStatus(200);
        $sources = $response->json('sources');
        $this->assertCount(2, $sources);

        foreach ($sources as $source) {
            $this->assertArrayHasKey('id', $source);
            $this->assertArrayHasKey('name', $source);
            $this->assertArrayHasKey('type', $source);
            $this->assertArrayHasKey('currency', $source);
            $this->assertArrayHasKey('current_balance', $source);
        }
    }

    public function test_dashboard_returns_category_distribution(): void
    {
        $expenseCategory = Category::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'EXPENSE',
            'name' => 'Comida',
            'color' => '#dc3545',
            'icon' => 'utensils',
        ]);

        Category::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'INCOME',
            'name' => 'Sueldo',
            'color' => '#28a745',
            'icon' => 'briefcase',
        ]);

        Movement::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'EXPENSE',
            'amount' => 15000,
            'currency' => 'ARS',
            'category_id' => $expenseCategory->id,
            'source_id' => $this->source->id,
            'operation_date' => now(),
            'status' => 'CONFIRMED',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/dashboard');

        $response->assertStatus(200);
        $distribution = $response->json('category_distribution');
        $this->assertCount(1, $distribution);
        $this->assertEquals('Comida', $distribution[0]['category']);
        $this->assertEquals(15000, $distribution[0]['amount']);
        $this->assertEquals('#dc3545', $distribution[0]['color']);
    }

    public function test_dashboard_returns_recent_movements(): void
    {
        Movement::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'source_id' => $this->source->id,
            'status' => 'CONFIRMED',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/dashboard');

        $response->assertStatus(200);
        $recent = $response->json('recent_movements');
        $this->assertLessThanOrEqual(10, count($recent)); // Default limit
    }

    public function test_dashboard_filters_by_period(): void
    {
        // Use fixed dates to avoid Carbon subMonth() edge cases at month boundaries
        $currentMonthDate = '2026-10-15'; // Clearly in October
        $lastMonthDate = '2026-09-15';    // Clearly in September

        // Current month
        $currentMonthMovement = Movement::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'INCOME',
            'amount' => 30000,
            'source_id' => $this->source->id,
            'operation_date' => $currentMonthDate,
            'status' => 'CONFIRMED',
        ]);

        // Last month
        $lastMonthMovement = Movement::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'INCOME',
            'amount' => 20000,
            'source_id' => $this->source->id,
            'operation_date' => $lastMonthDate,
            'status' => 'CONFIRMED',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/dashboard?period=month');

        $response->assertStatus(200);

        // Should only include current month income (October)
        $this->assertEquals(30000, $response->json('summary.income'));
    }

    public function test_dashboard_includes_goals_summary(): void
    {
        Goal::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Meta 1',
            'target_amount' => 100000,
            'current_amount' => 30000,
            'status' => 'ACTIVE',
        ]);

        Goal::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Meta 2',
            'target_amount' => 50000,
            'current_amount' => 50000,
            'status' => 'COMPLETED',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/dashboard');

        $response->assertStatus(200);
        $this->assertArrayHasKey('goals', $response->json());
        $goals = $response->json('goals');
        $this->assertCount(2, $goals);
    }

    public function test_dashboard_includes_budgets_summary(): void
    {
        $expenseCategory = Category::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'EXPENSE',
            'name' => 'Transporte',
        ]);

        Budget::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $expenseCategory->id,
            'amount' => 20000,
            'period' => 'MONTHLY',
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/dashboard');

        $response->assertStatus(200);
        $this->assertArrayHasKey('budgets', $response->json());
        $budgets = $response->json('budgets');
        $this->assertCount(1, $budgets);
    }

    public function test_dashboard_includes_unread_notifications_count(): void
    {
        Notification::factory()->count(3)->create(['user_id' => $this->user->id, 'read_at' => null]);
        Notification::factory()->count(2)->create(['user_id' => $this->user->id, 'read_at' => now()]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/dashboard');

        $response->assertStatus(200);
        $this->assertArrayHasKey('unread_notifications_count', $response->json());
        $this->assertEquals(3, $response->json('unread_notifications_count'));
    }

    public function test_unauthorized_user_cannot_access_dashboard(): void
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->getJson('/api/dashboard');

        $response->assertStatus(401);
    }
}