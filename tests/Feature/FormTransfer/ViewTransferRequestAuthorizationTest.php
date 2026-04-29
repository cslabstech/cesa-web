<?php

test('view transfer request page gates mutating header actions with update authorization', function () {
    $contents = file_get_contents(
        base_path('plugins/cesa/form-transfer/src/Filament/Resources/TransferRequestResource/Pages/ViewTransferRequest.php')
    );

    expect($contents)->toBeString()
        ->toContain('use Illuminate\Support\Facades\Gate;')
        ->and(substr_count($contents, "Gate::allows('update', \$record)"))->toBe(3)
        ->and(substr_count($contents, "Gate::authorize('update', \$record);"))->toBe(3);
});
