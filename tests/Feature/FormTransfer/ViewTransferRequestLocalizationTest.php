<?php

test('view transfer request page uses translation keys for admin actions and notifications', function () {
    $contents = file_get_contents(
        base_path('plugins/cesa/form-transfer/src/Filament/Resources/TransferRequestResource/Pages/ViewTransferRequest.php')
    );

    expect($contents)->toBeString()
        ->toContain("__('form-transfer::filament/resources/transfer-request/view.transfer_request.actions.download_pdf')")
        ->toContain("__('form-transfer::filament/resources/transfer-request/view.transfer_request.actions.resend_pending_approver')")
        ->toContain("__('form-transfer::filament/resources/transfer-request/actions.edit_realization')")
        ->toContain("__('form-transfer::filament/resources/transfer-request/view.transfer_request.actions.resend_notification_heading')")
        ->toContain("__('form-transfer::filament/resources/transfer-request/view.transfer_request.notifications.approval_completed_title')")
        ->toContain('form-transfer::filament/resources/transfer-request/view.transfer_request.notifications.notification_resent_body')
        ->not->toContain("->label('Download PDF')")
        ->not->toContain("->label('Kirim ulang ke approver pending')")
        ->not->toContain("->modalHeading('Kirim ulang notifikasi')")
        ->not->toContain("->title('Approval sudah selesai')")
        ->not->toContain("->title('Notifikasi dikirim ulang')");
});

test('view transfer request translations exist for english and indonesian locales', function () {
    $english = require base_path('plugins/cesa/form-transfer/resources/lang/en/filament/resources/transfer-request/view.php');
    $indonesian = require base_path('plugins/cesa/form-transfer/resources/lang/id/filament/resources/transfer-request/view.php');

    expect(data_get($english, 'transfer_request.actions.download_pdf'))->toBe('Download PDF')
        ->and(data_get($indonesian, 'transfer_request.actions.download_pdf'))->toBe('Unduh PDF')
        ->and(data_get($english, 'transfer_request.notifications.notification_resent_body'))->toBe('Sent to :approver.')
        ->and(data_get($indonesian, 'transfer_request.notifications.notification_resent_body'))->toBe('Dikirim ke :approver.');
});
