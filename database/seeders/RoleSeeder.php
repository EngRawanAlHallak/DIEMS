<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Facades\Hash;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // تنظيف الكاش لضمان عدم وجود تضارب
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. إنشاء الأدوار (Roles) مع تحديد الـ Guard
        $roles = [
            'admin',
            'company',
            'gate_operator'
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'sanctum'
            ]);
        }

        // 2. إنشاء الآدمن الأول (System Admin)
        // بما أن الـ ID هو UUID، سيقوم الموديل بتوليده تلقائياً عند الإنشاء
        $admin = User::firstOrCreate(
            ['email' => 'rawansami.un@gmail.com'],
            [
                'name'     => 'DIEMS Admin',
                'phonenumber' => '+963998047973',
                'password' => Hash::make('Admin@123456'),
            ]
        );

        // 3. إسناد الدور (Assign Role)
        // تأكدي أن موديل User يحتوي على: protected $guard_name = 'sanctum';
        if (!$admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

//        $this->command->info('Successfully seeded: admin@diems.sy');
    }
}
