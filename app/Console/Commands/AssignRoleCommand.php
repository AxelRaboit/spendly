<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use ValueError;

#[Signature('user:assign-role {email : User email} {role : Role name (ROLE_USER or ROLE_DEV)}')]
#[Description('Assign a role to a user')]
class AssignRoleCommand extends Command
{
    public function handle(): int
    {
        $email = $this->argument('email');
        $roleName = $this->argument('role');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error(sprintf('User with email %s not found', $email));

            return 1;
        }

        try {
            $role = UserRole::from($roleName);
        } catch (ValueError) {
            $this->error('Role must be one of: '.implode(', ', array_map(fn ($r) => $r->value, UserRole::cases())));

            return 1;
        }

        $user->syncRoles($role->value);

        $this->info(sprintf('✅ Assigned role %s to %s', $role->value, $user->email));

        return 0;
    }
}
