<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure ROLE_USER exists
        Role::firstOrCreate(['name' => UserRole::User->value, 'guard_name' => 'web']);

        // Assign ROLE_USER to all users without a role
        $users = User::doesntHave('roles')->get();
        foreach ($users as $user) {
            $user->assignRole(UserRole::User->value);
        }
    }

    public function down(): void
    {
        // Remove ROLE_USER from users (optional, you might want to leave it)
        // This is a data-destructive operation, so we leave it empty
    }
};
