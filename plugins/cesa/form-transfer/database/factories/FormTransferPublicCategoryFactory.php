<?php

namespace Cesa\FormTransfer\Database\Factories;

use Cesa\FormTransfer\Models\FormTransferPublicCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class FormTransferPublicCategoryFactory extends Factory
{
    protected $model = FormTransferPublicCategory::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'name'        => Str::headline($name),
            'slug'        => Str::slug($name),
            'description' => $this->faker->optional()->sentence(),
            'sort_order'  => $this->faker->numberBetween(1, 100),
            'is_active'   => true,
        ];
    }
}
