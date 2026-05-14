<?php

namespace Cesa\Padelnis\Database\Factories;

use Cesa\Padelnis\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_name'    => $this->faker->name(),
            'reservation_date' => $this->faker->dateTimeBetween('today', '+30 days')->format('Y-m-d'),
            'court'            => $this->faker->randomElement(array_values(config('padelnis.courts', ['Padel Court VIP Blue 1']))),
            'reservation_time' => $this->faker->randomElement(array_values(config('padelnis.slots', ['10:00 - 11:00']))),
            'transfer_amount'  => $this->faker->numberBetween(100000, 500000),
        ];
    }
}
