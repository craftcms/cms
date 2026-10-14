<?php

declare(strict_types=1);

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use Workbench\App\Models\Customer;

use function Orchestra\Testbench\workbench_path;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        (require workbench_path('database/migrations/2026_10_14_000000_create_customers_table.php'))->up();

        Customer::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'company' => 'Analytical Engines']);
        Customer::create(['name' => 'Grace Hopper', 'email' => 'grace@example.com', 'company' => 'Navy']);
        Customer::create(['name' => 'Alan Turing', 'email' => 'alan@example.com']);
    }
}
