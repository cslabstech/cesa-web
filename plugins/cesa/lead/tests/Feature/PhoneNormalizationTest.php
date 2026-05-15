<?php

namespace Cesa\Lead\Tests\Feature;

use Cesa\Lead\Models\Lead;
use Cesa\Lead\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class PhoneNormalizationTest extends TestCase
{
    /**
     * Data provider for various phone number formats
     */
    public static function phoneNumberFormatsProvider(): array
    {
        return [
            // [input, expected]
            'standard_08'             => ['08123456789', '628123456789'],
            'standard_62'             => ['628123456789', '628123456789'],
            'with_620_prefix'         => ['6208123456789', '628123456789'],
            'with_0062_prefix'        => ['006208123456789', '628123456789'],
            'with_00_prefix'          => ['008123456789', '628123456789'],
            'starting_with_8'         => ['8123456789', '628123456789'],
            'with_plus'               => ['+628123456789', '628123456789'],
            'with_dashes'             => ['0812-3456-789', '628123456789'],
            'with_spaces'             => ['0812 3456 789', '628123456789'],
            'with_parentheses'        => ['(0812) 3456789', '628123456789'],
            'with_dots'               => ['0812.3456.789', '628123456789'],
            'mixed_separators'        => ['+62 (812) 3456-789', '628123456789'],
            'multiple_spaces'         => ['0812  3456  789', '628123456789'],
            'leading_trailing_spaces' => [' 08123456789 ', '628123456789'],
            'with_country_code_plus'  => ['+62 812 3456 789', '628123456789'],
        ];
    }

    #[DataProvider('phoneNumberFormatsProvider')]
    public function test_phone_number_normalization_on_create(string $input, string $expected): void
    {
        $lead = Lead::factory()->create([
            'phone' => $input,
        ]);

        $this->assertEquals($expected, $lead->phone);
        $this->assertDatabaseHas('leads', [
            'id'    => $lead->id,
            'phone' => $expected,
        ]);
    }

    #[DataProvider('phoneNumberFormatsProvider')]
    public function test_phone_number_normalization_on_update(string $input, string $expected): void
    {
        $lead = Lead::factory()->create(['phone' => '08111111111']);

        $lead->update(['phone' => $input]);

        $this->assertEquals($expected, $lead->fresh()->phone);
        $this->assertDatabaseHas('leads', [
            'id'    => $lead->id,
            'phone' => $expected,
        ]);
    }

    public function test_phone_normalization_removes_all_non_numeric_characters(): void
    {
        $input = 'abc+62(812)-345.6789xyz';
        $expected = '628123456789';

        $lead = Lead::factory()->create(['phone' => $input]);

        $this->assertEquals($expected, $lead->phone);
    }

    public function test_phone_normalization_handles_emoji(): void
    {
        $input = '📱0812📞3456789✅';
        $expected = '628123456789';

        $lead = Lead::factory()->create(['phone' => $input]);

        $this->assertEquals($expected, $lead->phone);
    }

    public function test_phone_normalization_handles_unicode_characters(): void
    {
        $input = '０８１２３４５６７８９';

        $lead = new Lead;
        $lead->phone = $input;

        $this->assertSame('', Lead::normalizePhone($input));
        $this->assertNull($lead->phone);
    }

    public function test_phone_normalization_priority_order_620_before_62(): void
    {
        $input = '6208888888888';
        $expected = '628888888888';

        $lead = Lead::factory()->create(['phone' => $input]);

        $this->assertEquals($expected, $lead->phone);
    }

    public function test_phone_normalization_priority_order_0062_before_00(): void
    {
        $input = '00628888888888';
        $expected = '628888888888';

        $lead = Lead::factory()->create(['phone' => $input]);

        $this->assertEquals($expected, $lead->phone);
    }

    public function test_phone_normalization_handles_very_short_numbers(): void
    {
        $input = '08';
        $expected = '628';

        $lead = Lead::factory()->create(['phone' => $input]);

        $this->assertEquals($expected, $lead->phone);
    }

    public function test_phone_normalization_handles_very_long_numbers(): void
    {
        $input = '0812345678901234567890';
        $expected = '62812345678901234567890';

        $lead = Lead::factory()->create(['phone' => $input]);

        $this->assertEquals($expected, $lead->phone);
    }

    public function test_phone_normalization_handles_only_non_digits(): void
    {
        $input = '++--..  ';

        $lead = new Lead;
        $lead->phone = $input;

        $this->assertSame('', Lead::normalizePhone($input));
        $this->assertNull($lead->phone);
    }

    public function test_multiple_leads_different_phone_formats_stored_correctly(): void
    {
        $lead1 = Lead::factory()->create(['phone' => '08111111111']);
        $lead2 = Lead::factory()->create(['phone' => '+628222222222']);
        $lead3 = Lead::factory()->create(['phone' => '0062 833 333 3333']);

        $this->assertEquals('628111111111', $lead1->phone);
        $this->assertEquals('628222222222', $lead2->phone);
        $this->assertEquals('628333333333', $lead3->phone);

        $this->assertDatabaseCount('leads', 3);
    }

    public function test_phone_normalization_consistent_across_multiple_updates(): void
    {
        $lead = Lead::factory()->create(['phone' => '08123456789']);

        $this->assertEquals('628123456789', $lead->phone);

        $lead->update(['phone' => '+62-812-3456-789']);
        $this->assertEquals('628123456789', $lead->fresh()->phone);

        $lead->update(['phone' => '0812 3456 789']);
        $this->assertEquals('628123456789', $lead->fresh()->phone);

        $lead->update(['phone' => '628123456789']);
        $this->assertEquals('628123456789', $lead->fresh()->phone);
    }

    public function test_phone_starting_with_different_indonesian_prefixes(): void
    {
        // Common Indonesian mobile prefixes
        $prefixes = ['0811', '0812', '0813', '0821', '0822', '0852', '0853', '0856', '0857', '0858'];

        foreach ($prefixes as $prefix) {
            $input = $prefix.'12345678';
            $expected = '62'.substr($input, 1);

            $lead = Lead::factory()->create(['phone' => $input]);

            $this->assertEquals($expected, $lead->phone, "Failed for prefix {$prefix}");
        }
    }

    public function test_phone_normalization_handles_landline_format(): void
    {
        // Indonesian landline starting with 021 (Jakarta)
        $input = '02112345678';
        $expected = '622112345678';

        $lead = Lead::factory()->create(['phone' => $input]);

        $this->assertEquals($expected, $lead->phone);
    }

    public function test_phone_attribute_mutation_is_consistent(): void
    {
        $lead = new Lead;

        // Set multiple times with same number in different formats
        $lead->phone = '08123456789';
        $value1 = $lead->phone;

        $lead->phone = '+628123456789';
        $value2 = $lead->phone;

        $lead->phone = '0812-3456-789';
        $value3 = $lead->phone;

        $this->assertEquals($value1, $value2);
        $this->assertEquals($value2, $value3);
        $this->assertEquals('628123456789', $value1);
    }

    public function test_phone_with_extension_number(): void
    {
        $input = '0812345678 ext 123';
        $expected = '62812345678123';

        $lead = Lead::factory()->create(['phone' => $input]);

        $this->assertEquals($expected, $lead->phone);
    }

    public function test_phone_normalization_null_safety(): void
    {
        $lead = Lead::factory()->make(['phone' => null]);

        // Should not throw an error
        $this->assertNull($lead->phone);
    }

    public function test_phone_normalization_empty_string(): void
    {
        $lead = new Lead;
        $lead->phone = '';

        $this->assertSame('', Lead::normalizePhone(''));
        $this->assertNull($lead->phone);
    }
}
