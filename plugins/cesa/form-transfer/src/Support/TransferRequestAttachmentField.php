<?php

namespace Cesa\FormTransfer\Support;

use Filament\Forms\Components\FileUpload;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class TransferRequestAttachmentField
{
    public static function makeInvoice(string $name = 'invoice_path'): FileUpload
    {
        return FileUpload::make($name)
            ->label(__('form-transfer::filament/resources/transfer-request/fields.invoice'))
            ->disk('local')
            ->directory('form-transfer/invoices')
            ->visibility('private')
            ->multiple()
            ->downloadable()
            ->openable()
            ->fetchFileInformation(false)
            ->acceptedFileTypes([
                'application/pdf',
                'application/x-pdf',
                'image/jpeg',
                'image/jpg',
                'image/png',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->maxSize(5120)
            ->getUploadedFileNameForStorageUsing(
                fn (TemporaryUploadedFile $file): string => (string) str($file->getClientOriginalName())
                    ->limit(50, '')
                    ->append('-'.time().'.'.$file->getClientOriginalExtension())
            )
            ->helperText(__('form-transfer::filament/resources/transfer-request/helpers.invoice_upload'));
    }

    public static function makeAccountAttachment(string $name = 'account_attachment_path'): FileUpload
    {
        return FileUpload::make($name)
            ->label(__('form-transfer::filament/resources/transfer-request/fields.account_attachment'))
            ->disk('local')
            ->directory('form-transfer/account-attachments')
            ->visibility('private')
            ->multiple()
            ->downloadable()
            ->openable()
            ->fetchFileInformation(false)
            ->acceptedFileTypes([
                'application/pdf',
                'application/x-pdf',
                'image/jpeg',
                'image/jpg',
                'image/png',
            ])
            ->maxSize(5120)
            ->getUploadedFileNameForStorageUsing(
                fn (TemporaryUploadedFile $file): string => (string) str($file->getClientOriginalName())
                    ->limit(50, '')
                    ->append('-'.time().'.'.$file->getClientOriginalExtension())
            )
            ->helperText(__('form-transfer::filament/resources/transfer-request/helpers.account_attachment_upload'));
    }

    public static function makeRealizationProof(string $name = 'realization_proof_path'): FileUpload
    {
        return FileUpload::make($name)
            ->label(__('form-transfer::filament/resources/transfer-request/fields.realization_proof'))
            ->disk('local')
            ->directory('form-transfer/realizations')
            ->visibility('private')
            ->downloadable()
            ->openable()
            ->fetchFileInformation(false)
            ->acceptedFileTypes([
                'application/pdf',
                'image/jpeg',
                'image/png',
            ])
            ->maxSize(5120)
            ->getUploadedFileNameForStorageUsing(
                fn (TemporaryUploadedFile $file): string => (string) str($file->getClientOriginalName())
                    ->limit(50, '')
                    ->append('-'.time().'.'.$file->getClientOriginalExtension())
            )
            ->helperText(__('form-transfer::filament/resources/transfer-request/helpers.realization_proof_upload'));
    }
}
