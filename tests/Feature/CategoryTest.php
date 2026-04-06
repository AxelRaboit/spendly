<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Authorization
    // -------------------------------------------------------------------------

    public function test_guest_cannot_access_categories(): void
    {
        $this->get(route('categories.index'))->assertRedirect(route('login'));
    }

    public function test_user_cannot_view_another_users_category(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)
            ->get(route('categories.edit', $category))
            ->assertForbidden();
    }

    public function test_user_cannot_update_another_users_category(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)
            ->patch(route('categories.update', $category), ['name' => 'Hacked'])
            ->assertForbidden();
    }

    public function test_user_cannot_delete_another_users_category(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)
            ->delete(route('categories.destroy', $category))
            ->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // Index
    // -------------------------------------------------------------------------

    public function test_user_sees_only_their_categories(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        Category::factory()->create(['user_id' => $user->id, 'name' => 'Ma catégorie']);
        Category::factory()->create(['user_id' => $other->id, 'name' => 'Catégorie autre']);

        $response = $this->actingAs($user)->get(route('categories.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Categories/Index')
            ->where('categories.total', 1)
        );
    }

    // -------------------------------------------------------------------------
    // Create / Store
    // -------------------------------------------------------------------------

    public function test_user_can_create_a_category(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post(route('categories.store'), ['name' => 'Alimentation', 'wallet_id' => $wallet->id])
            ->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', [
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'name' => 'Alimentation',
        ]);
    }

    public function test_category_name_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('categories.store'), ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    // -------------------------------------------------------------------------
    // Update
    // -------------------------------------------------------------------------

    public function test_user_can_update_their_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'name' => 'Ancien nom']);

        $this->actingAs($user)
            ->patch(route('categories.update', $category), ['name' => 'Nouveau nom'])
            ->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Nouveau nom']);
    }

    // -------------------------------------------------------------------------
    // Delete
    // -------------------------------------------------------------------------

    public function test_user_can_delete_their_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'));

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    // -------------------------------------------------------------------------
    // Search filter
    // -------------------------------------------------------------------------

    public function test_search_filter_returns_matching_categories(): void
    {
        $user = User::factory()->create();
        Category::factory()->create(['user_id' => $user->id, 'name' => 'Alimentation']);
        Category::factory()->create(['user_id' => $user->id, 'name' => 'Transport']);

        $response = $this->actingAs($user)
            ->get(route('categories.index', ['search' => 'alim']));

        $response->assertInertia(fn ($page) => $page
            ->where('categories.total', 1)
            ->where('categories.data.0.name', 'Alimentation')
        );
    }

    public function test_search_filter_is_accent_insensitive(): void
    {
        $user = User::factory()->create();
        Category::factory()->create(['user_id' => $user->id, 'name' => 'Santé']);

        $response = $this->actingAs($user)
            ->get(route('categories.index', ['search' => 'sante']));

        $response->assertInertia(fn ($page) => $page
            ->where('categories.total', 1)
        );
    }
}
