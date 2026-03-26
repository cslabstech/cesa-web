<?php

namespace Cesa\Lead\Database\Factories;

use Cesa\Lead\Enums\PhoneTransactionRange;
use Cesa\Lead\Enums\StoreTeamPosition;
use Cesa\Lead\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Lead::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'                     => mb_strtoupper($this->faker->name()),
            'phone'                    => $this->faker->phoneNumber(),
            'address'                  => $this->faker->address(),
            'sales_person'             => $this->faker->name(),
            'store_team_position'      => $this->faker->randomElement(StoreTeamPosition::values()),
            'store_branch'             => $this->faker->randomElement(
                config('lead.store_branches', ['Default Branch'])
            ),
            'phone_transaction_range' => $this->faker->randomElement(PhoneTransactionRange::values()),
            'public_response_id'      => (string) Str::ulid(),
            'created_by'              => null,
        ];
    }
}
