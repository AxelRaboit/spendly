<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Goal;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoalTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Deposit — category requirement
    // -------------------------------------------------------------------------

    public function test_deposit_on_goal_without_wallet_does_not_require_category(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create([
            'user_id'       => $user->id,
            'wallet_id'     => null,
            'target_amount' => 1000,
            'saved_amount'  => 0,
        ]);

        $this->actingAs($user)
            ->from('/')
            ->post(route('goals.deposit', $goal), [
                'amount'      => 100,
                'category_id' => null,
                'date'        => '2026-04-01',
            ])
            ->assertRedirect('/');

        $this->assertDatabaseHas('goals', [
            'id'           => $goal->id,
            'saved_amount' => 100,
        ]);
    }

    public function test_deposit_on_goal_with_wallet_requires_category(): void
    {
        $user   = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);
        $goal   = Goal::factory()->create([
            'user_id'       => $user->id,
            'wallet_id'     => $wallet->id,
            'target_amount' => 1000,
            'saved_amount'  => 0,
        ]);

        $this->actingAs($user)
            ->from('/')
            ->post(route('goals.deposit', $goal), [
                'amount'      => 100,
                'category_id' => null,
                'date'        => '2026-04-01',
            ])
            ->assertSessionHasErrors('category_id');
    }

    public function test_deposit_on_goal_with_wallet_and_category_succeeds(): void
    {
        $user     = User::factory()->create();
        $wallet   = Wallet::factory()->create(['user_id' => $user->id]);
        $category = Category::factory()->create(['user_id' => $user->id]);
        $goal     = Goal::factory()->create([
            'user_id'       => $user->id,
            'wallet_id'     => $wallet->id,
            'target_amount' => 1000,
            'saved_amount'  => 0,
        ]);

        $this->actingAs($user)
            ->from('/')
            ->post(route('goals.deposit', $goal), [
                'amount'      => 200,
                'category_id' => $category->id,
                'date'        => '2026-04-01',
            ])
            ->assertRedirect('/');

        $this->assertDatabaseHas('goals', [
            'id'           => $goal->id,
            'saved_amount' => 200,
        ]);
    }

    // -------------------------------------------------------------------------
    // Deposit — goal reached notification
    // -------------------------------------------------------------------------

    public function test_deposit_that_completes_goal_flashes_reached_message(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create([
            'user_id'       => $user->id,
            'wallet_id'     => null,
            'target_amount' => 500,
            'saved_amount'  => 400,
        ]);

        $this->actingAs($user)
            ->from('/')
            ->post(route('goals.deposit', $goal), [
                'amount' => 100,
                'date'   => '2026-04-01',
            ])
            ->assertSessionHas('success', __('flash.goal.reached'));
    }

    public function test_deposit_that_does_not_complete_goal_flashes_deposit_message(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create([
            'user_id'       => $user->id,
            'wallet_id'     => null,
            'target_amount' => 1000,
            'saved_amount'  => 0,
        ]);

        $this->actingAs($user)
            ->from('/')
            ->post(route('goals.deposit', $goal), [
                'amount' => 100,
                'date'   => '2026-04-01',
            ])
            ->assertSessionHas('success', __('flash.goal.deposit'));
    }

    // -------------------------------------------------------------------------
    // Authorization
    // -------------------------------------------------------------------------

    public function test_user_cannot_deposit_on_another_users_goal(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $goal  = Goal::factory()->create([
            'user_id'       => $owner->id,
            'target_amount' => 1000,
            'saved_amount'  => 0,
        ]);

        $this->actingAs($other)
            ->post(route('goals.deposit', $goal), [
                'amount' => 100,
                'date'   => '2026-04-01',
            ])
            ->assertForbidden();
    }
}
