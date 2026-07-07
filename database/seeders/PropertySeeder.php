<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;

/*
|--------------------------------------------------------------------------
| PropertySeeder — Developer A
|--------------------------------------------------------------------------
| Requires UserSeeder to have run first (needs the landlord user). Creates a
| demo property with three units so the allocation flow is testable.
| Idempotent via updateOrCreate on natural keys.
|
| Run: php artisan db:seed --class=PropertySeeder
*/
class PropertySeeder extends Seeder
{
    public function run(): void
    {
        $landlord = User::where('email', 'landlord@example.com')->where('role', 'landlord')->first();

        if ($landlord === null) {
            $this->command?->warn('No landlord user found. Run UserSeeder first.');
            return;
        }

        $property = Property::updateOrCreate(
            ['landlord_id' => $landlord->id, 'name' => 'Riverview Apartments'],
            [
                'address'       => '12 River Road, Nairobi',
                'property_type' => 'apartment',
                'status'        => 'active',
                'description'   => 'Demo property seeded for testing.',
            ]
        );


        foreach (['A1' => 25000, 'A2' => 27000, 'B1' => 30000] as $number => $rent) {
            Unit::updateOrCreate(
                ['property_id' => $property->id, 'unit_number' => $number],
                ['rent_amount' => $rent, 'status' => 'available']
            );
        }

        $this->command?->info('Seeded 1 property with 3 available units for the demo landlord.');
    }
}
