<?php

namespace Cesa\Lead\Filament\Imports;

use Cesa\Lead\Enums\PhoneTransactionRange;
use Cesa\Lead\Enums\StoreTeamPosition;
use Cesa\Lead\Models\Lead;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Auth;

class LeadImporter extends Importer
{
    protected static ?string $model = Lead::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->label(__('lead::filament/resources/lead.imports.columns.name'))
                ->requiredMapping()
                ->guess(['name', 'full_name', 'customer_name', 'nama'])
                ->example('JOHN DOE')
                ->rules(['required', 'string', 'max:255']),

            ImportColumn::make('phone')
                ->label(__('lead::filament/resources/lead.imports.columns.phone'))
                ->requiredMapping()
                ->guess(['phone', 'telephone', 'mobile', 'contact_number', 'no_hp', 'telepon'])
                ->example('628123456789')
                ->rules([
                    'required',
                    'string',
                    'regex:/^628[1-9][0-9]{6,10}$/',
                ]),

            ImportColumn::make('address')
                ->label(__('lead::filament/resources/lead.imports.columns.address'))
                ->requiredMapping()
                ->guess(['address', 'location', 'street_address', 'alamat'])
                ->example('Jl. Example No. 123, Jakarta')
                ->rules(['required', 'string', 'max:500']),

            ImportColumn::make('sales_person')
                ->label(__('lead::filament/resources/lead.imports.columns.sales_person'))
                ->requiredMapping()
                ->guess(['sales_person', 'sales_rep', 'representative', 'sales', 'nama_sales'])
                ->example('Jane Smith')
                ->rules(['required', 'string', 'max:255']),

            ImportColumn::make('store_team_position')
                ->label(__('lead::filament/resources/lead.imports.columns.store_team_position'))
                ->requiredMapping()
                ->guess(['store_team_position', 'position', 'role', 'jabatan', 'posisi'])
                ->example('Kepala Toko')
                ->castStateUsing(fn (?string $state): ?string => static::castStoreTeamPosition($state))
                ->rules(['required', 'string', 'in:'.implode(',', StoreTeamPosition::values())]),

            ImportColumn::make('store_branch')
                ->label(__('lead::filament/resources/lead.imports.columns.store_branch'))
                ->requiredMapping()
                ->guess(['store_branch', 'branch', 'store_location', 'cabang', 'toko'])
                ->example('Complete Selular Ciledug')
                ->rules(['required', 'string', 'max:255']),

            ImportColumn::make('phone_transaction_range')
                ->label(__('lead::filament/resources/lead.imports.columns.phone_transaction_range'))
                ->guess(['phone_transaction_range', 'price_range', 'transaction_amount', 'range_harga', 'transaksi'])
                ->example('Harga 2 - 3 juta')
                ->castStateUsing(fn (?string $state): ?string => static::castPhoneTransactionRange($state))
                ->rules(['nullable', 'string', 'in:'.implode(',', PhoneTransactionRange::values())]),

            ImportColumn::make('created_at'),
            ImportColumn::make('updated_at'),
        ];
    }

    /**
     * Cast store team position value to enum value.
     */
    protected static function castStoreTeamPosition(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $normalizedValue = strtolower(trim($value));

        // Map common variations to exact enum values
        $mapping = [
            'kepala toko'      => 'Kepala Toko',
            'store head'       => 'Kepala Toko',
            'manager'          => 'Kepala Toko',
            'store manager'    => 'Kepala Toko',
            'promotor'         => 'Promotor',
            'promo'            => 'Promotor',
            'sales promoter'   => 'Promotor',
            'kasir'            => 'Kasir',
            'cashier'          => 'Kasir',
            'frontliner'       => 'Frontliner',
            'front line'       => 'Frontliner',
            'customer service' => 'Frontliner',
        ];

        // Try exact match first (case-insensitive)
        foreach (StoreTeamPosition::cases() as $case) {
            if (strtolower($case->value) === $normalizedValue) {
                return $case->value;
            }
        }

        // Try mapping
        return $mapping[$normalizedValue] ?? null;
    }

    /**
     * Cast phone transaction range value to enum value.
     */
    protected static function castPhoneTransactionRange(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $normalizedValue = strtolower(trim($value));

        // Map common variations to exact enum values
        $mapping = [
            'harga di bawah 2 juta' => 'Harga di bawah 2 juta',
            'below 2 million'       => 'Harga di bawah 2 juta',
            '< 2 juta'              => 'Harga di bawah 2 juta',
            'harga 2 - 3 juta'      => 'Harga 2 - 3 juta',
            'harga 2-3 juta'        => 'Harga 2 - 3 juta',
            '2-3 juta'              => 'Harga 2 - 3 juta',
            '2 to 3 million'        => 'Harga 2 - 3 juta',
            'harga 3 - 4 juta'      => 'Harga 3 - 4 juta',
            'harga 3-4 juta'        => 'Harga 3 - 4 juta',
            '3-4 juta'              => 'Harga 3 - 4 juta',
            '3 to 4 million'        => 'Harga 3 - 4 juta',
            'harga 4 - 7 juta'      => 'Harga 4 - 7 juta',
            'harga 4-7 juta'        => 'Harga 4 - 7 juta',
            '4-7 juta'              => 'Harga 4 - 7 juta',
            '4 to 7 million'        => 'Harga 4 - 7 juta',
            'harga di atas 7 juta'  => 'Harga di atas 7 juta',
            'above 7 million'       => 'Harga di atas 7 juta',
            '> 7 juta'              => 'Harga di atas 7 juta',
        ];

        // Try exact match first (case-insensitive)
        foreach (PhoneTransactionRange::cases() as $case) {
            if (strtolower($case->value) === $normalizedValue) {
                return $case->value;
            }
        }

        // Try mapping
        return $mapping[$normalizedValue] ?? null;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        return __('lead::filament/resources/lead.imports.notifications.completed_body', [
            'success' => number_format($import->successful_rows),
            'failed'  => number_format($import->getFailedRowsCount()),
        ]);
    }

    public static function getCompletedNotificationTitle(Import $import): string
    {
        return __('lead::filament/resources/lead.imports.notifications.completed_title');
    }

    public function resolveRecord(): ?Lead
    {
        $phone = $this->getData()['phone'] ?? null;

        if (blank($phone)) {
            return null;
        }

        return Lead::query()->firstOrNew(['phone' => $phone]);
    }

    protected function beforeSave(): void
    {
        $record = $this->getRecord();

        if (! $record instanceof Lead) {
            return;
        }

        if (! $record->exists) {
            $record->created_by = Auth::id();
        }
    }
}
