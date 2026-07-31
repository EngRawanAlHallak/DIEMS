<?php

namespace Database\Seeders;

use App\Models\Globalsetting;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
      $this->call([
          LandingPageSettingSeeder::class,
          CompanyPageSettingSeeder::class,
          PricingTierSeeder::class,
          RoleSeeder::class,
          WelcomePageSeeder::class,
          DataSeeder::class,
          EventSeeder::class,
          TicketSeeder::class,
        ]);
    }
}
