<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Authorization
    // -------------------------------------------------------------------------

    public function test_guest_cannot_access_transactions(): void
    {
        $this->get(route('transactions.index'))->assertRedirect(route('login'));
    }

    public function test_user_cannot_edit_another_users_transaction(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $owner->id]);
        $transaction = Transaction::factory()->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
        ]);

        $this->actingAs($other)
            ->get(route('transactions.edit', $transaction))
            ->assertForbidden();
    }

    public function test_user_cannot_update_another_users_transaction(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $owner->id]);
        $transaction = Transaction::factory()->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
        ]);

        $this->actingAs($other)
            ->patch(route('transactions.update', $transaction), [
                'category_id' => $category->id,
                'amount' => 99,
                'date' => '2026-01-01',
            ])
            ->assertForbidden();
    }

    public function test_user_cannot_delete_another_users_transaction(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $owner->id]);
        $transaction = Transaction::factory()->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
        ]);

        $this->actingAs($other)
            ->delete(route('transactions.destroy', $transaction))
            ->assertForbidden();
    }

    public function test_user_cannot_use_another_users_category(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $otherCategory = Category::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user)
            ->post(route('transactions.store'), [
                'category_id' => $otherCategory->id,
                'amount' => 50,
                'date' => '2026-01-01',
            ])
            ->assertSessionHasErrors('category_id');
    }

    // -------------------------------------------------------------------------
    // Index
    // -------------------------------------------------------------------------

    public function test_user_sees_only_their_transactions(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $cat1 = Category::factory()->create(['user_id' => $user->id]);
        $cat2 = Category::factory()->create(['user_id' => $other->id]);

        Transaction::factory()->create(['user_id' => $user->id, 'category_id' => $cat1->id]);
        Transaction::factory()->create(['user_id' => $other->id, 'category_id' => $cat2->id]);

        $response = $this->actingAs($user)->get(route('transactions.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Transactions/Index')
            ->where('transactions.total', 1)
        );
    }

    // -------------------------------------------------------------------------
    // Create / Store
    // -------------------------------------------------------------------------

    public function test_user_can_create_a_transaction(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post(route('transactions.store'), [
                'category_id' => $category->id,
                'amount' => 42.50,
                'description' => 'Courses',
                'date' => '2026-01-15',
            ])
            ->assertRedirect(route('transactions.index'));

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 42.50,
            'description' => 'Courses',
        ]);
    }

    public function test_transaction_requires_amount_and_date(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post(route('transactions.store'), ['category_id' => $category->id])
            ->assertSessionHasErrors(['amount', 'date']);
    }

    public function test_transaction_amount_must_be_positive(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post(route('transactions.store'), [
                'category_id' => $category->id,
                'amount' => -10,
                'date' => '2026-01-01',
            ])
            ->assertSessionHasErrors('amount');
    }

    // -------------------------------------------------------------------------
    // Update
    // -------------------------------------------------------------------------

    public function test_user_can_update_their_transaction(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);
        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 10,
        ]);

        $this->actingAs($user)
            ->patch(route('transactions.update', $transaction), [
                'category_id' => $category->id,
                'amount' => 99.99,
                'date' => '2026-03-01',
            ])
            ->assertRedirect(route('transactions.index'));

        $this->assertDatabaseHas('transactions', ['id' => $transaction->id, 'amount' => 99.99]);
    }

    // -------------------------------------------------------------------------
    // Delete
    // -------------------------------------------------------------------------

    public function test_user_can_delete_their_transaction(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);
        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $this->actingAs($user)
            ->delete(route('transactions.destroy', $transaction))
            ->assertRedirect(route('transactions.index'));

        $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
    }

    // -------------------------------------------------------------------------
    // Filters
    // -------------------------------------------------------------------------

    public function test_search_filter_returns_matching_transactions(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        Transaction::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'description' => 'Courses Carrefour',
        ]);
        Transaction::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'description' => 'Essence',
        ]);

        $response = $this->actingAs($user)
            ->get(route('transactions.index', ['search' => 'courses']));

        $response->assertInertia(fn ($page) => $page
            ->where('transactions.total', 1)
            ->where('transactions.data.0.description', 'Courses Carrefour')
        );
    }

    public function test_category_filter_returns_matching_transactions(): void
    {
        $user = User::factory()->create();
        $cat1 = Category::factory()->create(['user_id' => $user->id]);
        $cat2 = Category::factory()->create(['user_id' => $user->id]);

        Transaction::factory()->create(['user_id' => $user->id, 'category_id' => $cat1->id]);
        Transaction::factory()->create(['user_id' => $user->id, 'category_id' => $cat2->id]);

        $response = $this->actingAs($user)
            ->get(route('transactions.index', ['category_id' => $cat1->id]));

        $response->assertInertia(fn ($page) => $page
            ->where('transactions.total', 1)
        );
    }
}
