<?php

namespace Cesa\Lead\Tests\Feature;

use Cesa\Lead\Models\Lead;
use Cesa\Lead\Tests\TestCase;
use Illuminate\Support\Facades\Validator;

class LeadValidationTest extends TestCase
{
    protected function getValidationRules(): array
    {
        return [
            'name'  => 'required|string|max:255',
            'phone' => [
                'required',
                'string',
                'max:15',
                'unique:leads,phone',
                function (string $attribute, $value, $fail) {
                    if (! preg_match('/^62[0-9]{8,}$/', (string) $value)) {
                        $fail('Format nomor telepon harus 62xxxxxxxxxx (minimal 10 digit) dan hanya angka.');
                    }
                },
            ],
            'address'                      => 'required|string',
            'sales_person'                 => 'required|string|max:255',
            'store_team_position'          => 'required|in:Kepala Toko,Promotor,Kasir,Frontliner',
            'store_branch'                 => 'required|string',
            'phone_transaction_range'      => 'nullable|in:Harga di bawah 2 juta,Harga 2 - 3 juta,Harga 3 - 4 juta,Harga 4 - 7 juta,Harga di atas 7 juta',
        ];
    }

    public function test_validation_passes_with_valid_data(): void
    {
        $data = [
            'name'                         => 'John Doe',
            'phone'                        => '628123456789',
            'address'                      => 'Jl. Test No. 123',
            'sales_person'                 => 'Jane Doe',
            'store_team_position'          => 'Kepala Toko',
            'store_branch'                 => 'Complete Selular Babakan',
            'phone_transaction_range'      => 'Harga di bawah 2 juta',
        ];

        $validator = Validator::make($data, $this->getValidationRules());

        $this->assertFalse($validator->fails());
    }

    public function test_name_is_required(): void
    {
        $data = [
            'phone'               => '628123456789',
            'address'             => 'Jl. Test No. 123',
            'sales_person'        => 'Jane Doe',
            'store_team_position' => 'Kepala Toko',
            'store_branch'        => 'Complete Selular Babakan',
        ];

        $validator = Validator::make($data, $this->getValidationRules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_name_cannot_exceed_255_characters(): void
    {
        $data = [
            'name'                => str_repeat('a', 256),
            'phone'               => '628123456789',
            'address'             => 'Jl. Test No. 123',
            'sales_person'        => 'Jane Doe',
            'store_team_position' => 'Kepala Toko',
            'store_branch'        => 'Complete Selular Babakan',
        ];

        $validator = Validator::make($data, $this->getValidationRules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_name_accepts_255_characters(): void
    {
        $data = [
            'name'                => str_repeat('a', 255),
            'phone'               => '628123456789',
            'address'             => 'Jl. Test No. 123',
            'sales_person'        => 'Jane Doe',
            'store_team_position' => 'Kepala Toko',
            'store_branch'        => 'Complete Selular Babakan',
        ];

        $validator = Validator::make($data, $this->getValidationRules());

        $this->assertFalse($validator->fails());
    }

    public function test_phone_is_required(): void
    {
        $data = [
            'name'                => 'John Doe',
            'address'             => 'Jl. Test No. 123',
            'sales_person'        => 'Jane Doe',
            'store_team_position' => 'Kepala Toko',
            'store_branch'        => 'Complete Selular Babakan',
        ];

        $validator = Validator::make($data, $this->getValidationRules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('phone', $validator->errors()->toArray());
    }

    public function test_phone_must_be_unique(): void
    {
        Lead::factory()->create(['phone' => '628123456789']);

        $data = [
            'name'                => 'John Doe',
            'phone'               => '628123456789',
            'address'             => 'Jl. Test No. 123',
            'sales_person'        => 'Jane Doe',
            'store_team_position' => 'Kepala Toko',
            'store_branch'        => 'Complete Selular Babakan',
        ];

        $validator = Validator::make($data, $this->getValidationRules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('phone', $validator->errors()->toArray());
    }

    public function test_phone_must_start_with_62(): void
    {
        $data = [
            'name'                => 'John Doe',
            'phone'               => '08123456789',
            'address'             => 'Jl. Test No. 123',
            'sales_person'        => 'Jane Doe',
            'store_team_position' => 'Kepala Toko',
            'store_branch'        => 'Complete Selular Babakan',
        ];

        $validator = Validator::make($data, $this->getValidationRules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('phone', $validator->errors()->toArray());
    }

    public function test_phone_must_have_minimum_10_digits(): void
    {
        $data = [
            'name'                => 'John Doe',
            'phone'               => '6281234', // Only 7 digits total
            'address'             => 'Jl. Test No. 123',
            'sales_person'        => 'Jane Doe',
            'store_team_position' => 'Kepala Toko',
            'store_branch'        => 'Complete Selular Babakan',
        ];

        $validator = Validator::make($data, $this->getValidationRules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('phone', $validator->errors()->toArray());
    }

    public function test_phone_accepts_exactly_10_digits(): void
    {
        $data = [
            'name'                => 'John Doe',
            'phone'               => '6281234567', // Exactly 10 digits
            'address'             => 'Jl. Test No. 123',
            'sales_person'        => 'Jane Doe',
            'store_team_position' => 'Kepala Toko',
            'store_branch'        => 'Complete Selular Babakan',
        ];

        $validator = Validator::make($data, $this->getValidationRules());

        $this->assertFalse($validator->fails());
    }

    public function test_phone_cannot_exceed_15_characters(): void
    {
        $data = [
            'name'                => 'John Doe',
            'phone'               => '6281234567890123', // 16 characters
            'address'             => 'Jl. Test No. 123',
            'sales_person'        => 'Jane Doe',
            'store_team_position' => 'Kepala Toko',
            'store_branch'        => 'Complete Selular Babakan',
        ];

        $validator = Validator::make($data, $this->getValidationRules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('phone', $validator->errors()->toArray());
    }

    public function test_phone_accepts_15_characters(): void
    {
        $data = [
            'name'                => 'John Doe',
            'phone'               => '628123456789012', // Exactly 15 characters
            'address'             => 'Jl. Test No. 123',
            'sales_person'        => 'Jane Doe',
            'store_team_position' => 'Kepala Toko',
            'store_branch'        => 'Complete Selular Babakan',
        ];

        $validator = Validator::make($data, $this->getValidationRules());

        $this->assertFalse($validator->fails());
    }

    public function test_phone_must_contain_only_numbers(): void
    {
        $data = [
            'name'                => 'John Doe',
            'phone'               => '62abc1234567',
            'address'             => 'Jl. Test No. 123',
            'sales_person'        => 'Jane Doe',
            'store_team_position' => 'Kepala Toko',
            'store_branch'        => 'Complete Selular Babakan',
        ];

        $validator = Validator::make($data, $this->getValidationRules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('phone', $validator->errors()->toArray());
    }

    public function test_address_is_required(): void
    {
        $data = [
            'name'                => 'John Doe',
            'phone'               => '628123456789',
            'sales_person'        => 'Jane Doe',
            'store_team_position' => 'Kepala Toko',
            'store_branch'        => 'Complete Selular Babakan',
        ];

        $validator = Validator::make($data, $this->getValidationRules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('address', $validator->errors()->toArray());
    }

    public function test_address_can_be_long_text(): void
    {
        $data = [
            'name'                => 'John Doe',
            'phone'               => '628123456789',
            'address'             => str_repeat('Jl. Test No. 123 ', 100),
            'sales_person'        => 'Jane Doe',
            'store_team_position' => 'Kepala Toko',
            'store_branch'        => 'Complete Selular Babakan',
        ];

        $validator = Validator::make($data, $this->getValidationRules());

        $this->assertFalse($validator->fails());
    }

    public function test_sales_person_is_required(): void
    {
        $data = [
            'name'                => 'John Doe',
            'phone'               => '628123456789',
            'address'             => 'Jl. Test No. 123',
            'store_team_position' => 'Kepala Toko',
            'store_branch'        => 'Complete Selular Babakan',
        ];

        $validator = Validator::make($data, $this->getValidationRules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('sales_person', $validator->errors()->toArray());
    }

    public function test_sales_person_cannot_exceed_255_characters(): void
    {
        $data = [
            'name'                => 'John Doe',
            'phone'               => '628123456789',
            'address'             => 'Jl. Test No. 123',
            'sales_person'        => str_repeat('a', 256),
            'store_team_position' => 'Kepala Toko',
            'store_branch'        => 'Complete Selular Babakan',
        ];

        $validator = Validator::make($data, $this->getValidationRules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('sales_person', $validator->errors()->toArray());
    }

    public function test_store_team_position_is_required(): void
    {
        $data = [
            'name'          => 'John Doe',
            'phone'         => '628123456789',
            'address'       => 'Jl. Test No. 123',
            'sales_person'  => 'Jane Doe',
            'store_branch'  => 'Complete Selular Babakan',
        ];

        $validator = Validator::make($data, $this->getValidationRules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('store_team_position', $validator->errors()->toArray());
    }

    public function test_store_team_position_must_be_valid_option(): void
    {
        $data = [
            'name'                => 'John Doe',
            'phone'               => '628123456789',
            'address'             => 'Jl. Test No. 123',
            'sales_person'        => 'Jane Doe',
            'store_team_position' => 'Invalid Position',
            'store_branch'        => 'Complete Selular Babakan',
        ];

        $validator = Validator::make($data, $this->getValidationRules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('store_team_position', $validator->errors()->toArray());
    }

    public function test_store_team_position_accepts_all_valid_options(): void
    {
        $validOptions = ['Kepala Toko', 'Promotor', 'Kasir', 'Frontliner'];

        foreach ($validOptions as $option) {
            $data = [
                'name'                => 'John Doe',
                'phone'               => '62812345678'.rand(10, 99),
                'address'             => 'Jl. Test No. 123',
                'sales_person'        => 'Jane Doe',
                'store_team_position' => $option,
                'store_branch'        => 'Complete Selular Babakan',
            ];

            $validator = Validator::make($data, $this->getValidationRules());

            $this->assertFalse($validator->fails(), "Failed for jabatan: {$option}");
        }
    }

    public function test_store_branch_is_required(): void
    {
        $data = [
            'name'                => 'John Doe',
            'phone'               => '628123456789',
            'address'             => 'Jl. Test No. 123',
            'sales_person'        => 'Jane Doe',
            'store_team_position' => 'Kepala Toko',
        ];

        $validator = Validator::make($data, $this->getValidationRules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('store_branch', $validator->errors()->toArray());
    }

    public function test_phone_transaction_range_is_nullable(): void
    {
        $data = [
            'name'                => 'John Doe',
            'phone'               => '628123456789',
            'address'             => 'Jl. Test No. 123',
            'sales_person'        => 'Jane Doe',
            'store_team_position' => 'Kepala Toko',
            'store_branch'        => 'Complete Selular Babakan',
        ];

        $validator = Validator::make($data, $this->getValidationRules());

        $this->assertFalse($validator->fails());
    }

    public function test_phone_transaction_range_must_be_valid_option_when_provided(): void
    {
        $data = [
            'name'                         => 'John Doe',
            'phone'                        => '628123456789',
            'address'                      => 'Jl. Test No. 123',
            'sales_person'                 => 'Jane Doe',
            'store_team_position'          => 'Kepala Toko',
            'store_branch'                 => 'Complete Selular Babakan',
            'phone_transaction_range'      => 'Invalid Range',
        ];

        $validator = Validator::make($data, $this->getValidationRules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('phone_transaction_range', $validator->errors()->toArray());
    }

    public function test_phone_transaction_range_accepts_all_valid_options(): void
    {
        $validOptions = [
            'Harga di bawah 2 juta',
            'Harga 2 - 3 juta',
            'Harga 3 - 4 juta',
            'Harga 4 - 7 juta',
            'Harga di atas 7 juta',
        ];

        foreach ($validOptions as $index => $option) {
            $data = [
                'name'                         => 'John Doe',
                'phone'                        => '62812345678'.sprintf('%02d', $index),
                'address'                      => 'Jl. Test No. 123',
                'sales_person'                 => 'Jane Doe',
                'store_team_position'          => 'Kepala Toko',
                'store_branch'                 => 'Complete Selular Babakan',
                'phone_transaction_range'      => $option,
            ];

            $validator = Validator::make($data, $this->getValidationRules());

            $this->assertFalse($validator->fails(), "Failed for range: {$option}");
        }
    }

    public function test_validation_fails_with_multiple_errors(): void
    {
        $data = [
            // Missing name
            'phone' => '12345', // Invalid phone
            // Missing address
            // Missing sales_person
            // Missing store_team_position
            // Missing store_branch
        ];

        $validator = Validator::make($data, $this->getValidationRules());

        $this->assertTrue($validator->fails());
        $errors = $validator->errors()->toArray();

        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('phone', $errors);
        $this->assertArrayHasKey('address', $errors);
        $this->assertArrayHasKey('sales_person', $errors);
        $this->assertArrayHasKey('store_team_position', $errors);
        $this->assertArrayHasKey('store_branch', $errors);
    }
}
