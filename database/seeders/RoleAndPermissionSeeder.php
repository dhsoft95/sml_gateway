<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create Super Admin Role
        $superAdmin = Role::create(['name' => 'super_admin']);

        // Assign all permissions to super admin
        $permissions = [
            'view_any_merchant',
            'view_merchant',
            'create_merchant',
            'update_merchant',
            'delete_merchant',
            'delete_any_merchant',
            // Add other resource permissions
            'view_any_invoice',
            'view_invoice',
            'create_invoice',
            'update_invoice',
            'delete_invoice',
            'delete_any_invoice',
            // Transaction permissions
            'view_any_transaction',
            'view_transaction',
            'create_transaction',
            'update_transaction',
            'delete_transaction',
            'delete_any_transaction',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        $superAdmin->givePermissionTo(Permission::all());

        // Create a test admin user
        $user = \App\Models\User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $user->assignRole('super_admin');
    }
}
