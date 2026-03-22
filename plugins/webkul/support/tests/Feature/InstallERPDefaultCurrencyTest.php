<?php

use Illuminate\Support\Facades\DB;
use Webkul\Support\Models\Currency;

require_once __DIR__.'/../Helpers/TestBootstrapHelper.php';

beforeEach(function () {
    TestBootstrapHelper::ensureERPInstalled();
});

it('uses the configured app currency as the default installed currency', function () {
    $configuredCurrencyCode = strtoupper((string) config('app.currency'));

    expect($configuredCurrencyCode)->not->toBe('');

    $configuredCurrency = Currency::query()
        ->where('name', $configuredCurrencyCode)
        ->first();

    expect($configuredCurrency)->not->toBeNull();
    expect($configuredCurrency->active)->toBeTrue();

    $defaultCurrencySetting = DB::table('settings')
        ->where('group', 'currency')
        ->where('name', 'default_currency_id')
        ->first();

    expect($defaultCurrencySetting)->not->toBeNull();
    expect(json_decode($defaultCurrencySetting->payload, true))->toBe($configuredCurrency->id);
});
