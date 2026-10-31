<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetsApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;
    protected Category $expenseCategory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test')->plainTextToken;

        // Create an expense category for budget tests
        $this->expenseCategory = Category::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'EXPENSE',
            'name' => 'Comida',
        ]);
    }

    public function test_user_can_create_budget(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/budgets', [
            'category_id' => $this->expenseCategory->id,
            'period' => 'MONTHLY',
            'amount' => 50000,
            'currency' => 'ARS',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'category_id',
                'period',
                'amount',
                'currency',
                'start_date',
                'end_date',
                'spent',
                'remaining',
                'percentage_used',
                'is_exceeded',
                'is_near_limit',
                'category',
                'created_at',
                'updated_at',
            ])
            ->assertJsonFragment([
                'category_id' => $this->expenseCategory->id,
                'period' => 'MONTHLY',
                'amount' => '50000.00',
                'currency' => 'ARS',
                'spent' => 0,
            ]);

        $this->assertDatabaseHas('budgets', [
            'user_id' => $this->user->id,
            'category_id' => $this->expenseCategory->id,
            'period' => 'MONTHLY',
            'amount' => 50000,
        ]);
    }

    public function test_user_can_list_budgets(): void
    {
        Budget::factory()->count(3)->create(['user_id' => $this->user->id, 'category_id' => $this->expenseCategory->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/budgets');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'category_id',
                    'period',
                    'amount',
                    'currency',
                    'start_date',
                    'end_date',
                    'spent',
                    'remaining',
                    'percentage_used',
                    'is_exceeded',
                    'is_near_limit',
                    'category',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertCount(3, $response->json());
    }

    public function test_user_can_view_single_budget(): void
    {
        $budget = Budget::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->expenseCategory->id,
            'amount' => 30000,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson("/api/budgets/{$budget->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $budget->id,
                'amount' => '30000.00',
            ]);
    }

    public function test_user_can_update_budget(): void
    {
        $budget = Budget::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->expenseCategory->id,
            'amount' => 20000,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->putJson("/api/budgets/{$budget->id}", [
            'amount' => 40000,
            'period' => 'WEEKLY',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'amount' => '40000.00',
                'period' => 'WEEKLY',
            ]);

        $this->assertDatabaseHas('budgets', [
            'id' => $budget->id,
            'amount' => 40000,
            'period' => 'WEEKLY',
        ]);
    }

    public function test_user_can_delete_budget(): void
    {
        $budget = Budget::factory()->create(['user_id' => $this->user->id, 'category_id' => $this->expenseCategory->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->deleteJson("/api/budgets/{$budget->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Budget deleted successfully',
            ]);

        // Check soft delete
        $this->assertSoftDeleted('budgets', ['id' => $budget->id]);
    }

    public function test_budget_spent_calculation(): void
    {
        // Create a budget
        $budget = Budget::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->expenseCategory->id,
            'amount' => 50000,
            'period' => 'MONTHLY',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ]);

        // Create some confirmed expense movements in this category
        $this->user->movements()->create([
            'type' => 'EXPENSE',
            'amount' => 15000,
            'currency' => 'ARS',
            'category_id' => $this->expenseCategory->id,
            'source_id' => $this->user->sources()->firstOrCreate(['name' => 'Test Source', 'type' => 'BANK', 'currency' => 'ARS', 'status' => 'ACTIVE'])->id,
            'operation_date' => '2026-10-15',
            'status' => 'CONFIRMED',
        ]);

        $this->user->movements()->create([
            'type' => 'EXPENSE',
            'amount' => 20000,
            'currency' => 'ARS',
            'category_id' => $this->expenseCategory->id,
            'source_id' => $this->user->sources()->first()->id,
            'operation_date' => '2026-10-20',
            'status' => 'CONFIRMED',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson("/api/budgets/{$budget->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'spent' => 35000,
                'remaining' => 15000,
                'percentage_used' => 70,
            ]);
    }

    public function test_budget_exceeded_flag(): void
    {
        $budget = Budget::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->expenseCategory->id,
            'amount' => 10000,
            'period' => 'MONTHLY',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ]);

        // Create expense exceeding budget
        $this->user->movements()->create([
            'type' => 'EXPENSE',
            'amount' => 15000,
            'currency' => 'ARS',
            'category_id' => $this->expenseCategory->id,
            'source_id' => $this->user->sources()->firstOrCreate(['name' => 'Test Source', 'type' => 'BANK', 'currency' => 'ARS', 'status' => 'ACTIVE'])->id,
            'operation_date' => '2026-10-15',
            'status' => 'CONFIRMED',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson("/api/budgets/{$budget->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'is_exceeded' => true,
                'is_near_limit' => true,
            ]);
    }

    public function test_budget_near_limit_flag(): void
    {
        $budget = Budget::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->expenseCategory->id,
            'amount' => 100000,
            'period' => 'MONTHLY',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ]);

        // Create expense at 85% of budget
        $this->user->movements()->create([
            'type' => 'EXPENSE',
            'amount' => 85000,
            'currency' => 'ARS',
            'category_id' => $this->expenseCategory->id,
            'source_id' => $this->user->sources()->firstOrCreate(['name' => 'Test Source', 'type' => 'BANK', 'currency' => 'ARS', 'status' => 'ACTIVE'])->id,
            'operation_date' => '2026-10-15',
            'status' => 'CONFIRMED',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson("/api/budgets/{$budget->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'is_exceeded' => false,
                'is_near_limit' => true,
            ]);
    }

    public function test_unauthorized_user_cannot_access_budgets(): void
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->getJson('/api/budgets');

        $response->assertStatus(401);
    }

    public function test_user_cannot_access_other_user_budgets(): void
    {
        $otherUser = User::factory()->create();
        $budget = Budget::factory()->create(['user_id' => $otherUser->id, 'category_id' => $this->expenseCategory->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson("/api/budgets/{$budget->id}");

        $response->assertStatus(404);
    }

    public function test_create_budget_validation_errors(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/budgets', [
            'category_id' => 99999,
            'period' => 'INVALID',
            'amount' => -100,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id', 'period', 'amount']);
    }

    public function test_budget_only_accepts_expense_categories(): void
    {
        $incomeCategory = Category::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'INCOME',
            'name' => 'Sueldo',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/budgets', [
            'category_id' => $incomeCategory->id,
            'period' => 'MONTHLY',
            'amount' => 50000,
            'start_date' => '2026-10-01',
        ]);

        // Should fail validation - budget only for expense categories
        $response->assertStatus(422);
    }
}