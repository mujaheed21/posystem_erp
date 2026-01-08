<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        Permission::firstOrCreate(['name' => 'purchase.create']);
        Permission::firstOrCreate(['name' => 'purchase.approve']);
        Permission::firstOrCreate(['name' => 'purchase.receive']);
        $procurement = Role::firstOrCreate(['name' => 'procurement']);
        $supervisor  = Role::firstOrCreate(['name' => 'supervisor']);
        $warehouse   = Role::firstOrCreate(['name' => 'warehouse-agent']);
        $procurement->syncPermissions([
            'purchase.create',
        ]);

        $supervisor->syncPermissions([
             'purchase.approve',
        ]);

        $warehouse->syncPermissions([
            'purchase.receive',
        ]);
    }
}
