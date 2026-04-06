<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $userRole = Role::firstOrCreate(['name' => UserRole::User->value, 'guard_name' => 'web']);
        $devRole = Role::firstOrCreate(['name' => UserRole::Dev->value, 'guard_name' => 'web']);

        $devRole->syncPermissions($userRole->permissions);

        echo "✅ Roles created with hierarchy:\n";
        echo "  - ".UserRole::User->value." (base role)\n";
        echo "  - ".UserRole::Dev->value." (inherits ".UserRole::User->value.")\n";
    }
}
