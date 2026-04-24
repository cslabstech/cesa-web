<?php

namespace Cesa\FormTransfer\Database\Factories;

use App\Models\User;
use Cesa\FormTransfer\Models\TransferRequest;
use Cesa\FormTransfer\Models\TransferRequestRealization;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransferRequestRealizationFactory extends Factory
{
    protected $model = TransferRequestRealization::class;

    public function definition(): array
    {
        return [
            'transfer_request_id' => TransferRequest::factory(),
            'user_id'             => User::factory(),
            'amount'              => $this->faker->randomFloat(2, 100_000, 10_000_000),
            'realized_at'         => $this->faker->date(),
            'proof_path'          => $this->faker->optional()->lexify('documents/realizations/REAL-????.pdf'),
            'notes'               => $this->faker->optional()->sentence(),
        ];
    }
}
