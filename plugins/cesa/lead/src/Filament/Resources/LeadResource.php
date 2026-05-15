<?php

namespace Cesa\Lead\Filament\Resources;

use Cesa\Lead\Filament\Resources\Lead\Pages;
use Cesa\Lead\Filament\Resources\Lead\Tables\LeadTable;
use Cesa\Lead\Models\Lead;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Webkul\PluginManager\Package;
use Webkul\Security\Traits\HasResourcePermissionQuery;

class LeadResource extends Resource
{
    use HasResourcePermissionQuery;

    protected static ?string $model = Lead::class;

    protected static string|\BackedEnum|null $navigationIcon = null;

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return Package::isPluginInstalled('lead');
    }

    public static function getNavigationLabel(): string
    {
        return __('lead::filament/resources/lead.navigation.title');
    }

    public static function getPluralModelLabel(): string
    {
        return __('lead::filament/resources/lead.plural');
    }

    public static function getModelLabel(): string
    {
        return __('lead::filament/resources/lead.singular');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.lead');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('lead::filament/resources/lead.form.sections.basic_information.title'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('lead::filament/resources/lead.form.sections.basic_information.fields.name'))
                            ->required()
                            ->maxLength(255)
                            ->placeholder(__('lead::filament/resources/lead.form.placeholders.name')),
                        Forms\Components\TextInput::make('phone')
                            ->label(__('lead::filament/resources/lead.form.sections.basic_information.fields.phone'))
                            ->tel()
                            ->required()
                            ->maxLength(15)
                            ->placeholder(__('lead::filament/resources/lead.form.placeholders.phone'))
                            ->live(onBlur: true)
                            ->helperText(fn (?string $operation = null, mixed $livewire = null): ?string => static::getWhatsAppValidationHelperText($operation, $livewire))
                            ->suffixAction(
                                Action::make('check_whatsapp')
                                    ->label(__('lead::views/public-lead-form.whatsapp_validation.action'))
                                    ->icon('heroicon-m-magnifying-glass')
                                    ->tooltip(__('lead::views/public-lead-form.whatsapp_validation.action'))
                                    ->action(fn (mixed $livewire = null): mixed => static::checkWhatsAppValidation($livewire))
                                    ->visible(fn (?string $operation = null, mixed $livewire = null): bool => static::shouldShowWhatsAppValidationAction($operation, $livewire))
                            )
                            ->afterStateUpdated(function ($state, callable $set, mixed $livewire = null): void {
                                $set('phone', Lead::normalizePhone((string) $state));

                                if (is_object($livewire) && method_exists($livewire, 'resetWhatsAppValidationFeedback')) {
                                    $livewire->resetWhatsAppValidationFeedback();
                                }
                            })
                            ->unique(Lead::class, 'phone', ignoreRecord: true)
                            ->rule(function () {
                                return function (string $attribute, $value, $fail) {
                                    // Validasi: harus mulai dengan 62 dan minimal 10 digit angka
                                    if (! preg_match('/^62[0-9]{8,}$/', (string) $value)) {
                                        $fail(__('lead::filament/resources/lead.validation.phone_format'));
                                    }
                                };
                            }),
                        Forms\Components\Textarea::make('address')
                            ->label(__('lead::filament/resources/lead.form.sections.basic_information.fields.address'))
                            ->required()
                            ->columnSpanFull()
                            ->placeholder(__('lead::filament/resources/lead.form.placeholders.address')),
                    ]),
                Section::make(__('lead::filament/resources/lead.form.sections.store_information.title'))
                    ->schema([
                        Forms\Components\TextInput::make('sales_person')
                            ->label(__('lead::filament/resources/lead.form.sections.store_information.fields.sales_person'))
                            ->required()
                            ->disabled(fn (?string $operation = null, mixed $livewire = null): bool => static::shouldDisableUntilWhatsAppValidation($operation, $livewire))
                            ->maxLength(255)
                            ->placeholder(__('lead::filament/resources/lead.form.placeholders.sales_person')),
                        Forms\Components\ToggleButtons::make('store_team_position')
                            ->label(__('lead::filament/resources/lead.form.sections.store_information.fields.store_team_position'))
                            ->options([
                                'Kepala Toko' => __('lead::filament/resources/lead.options.store_team_position.kepala_toko'),
                                'Promotor'    => __('lead::filament/resources/lead.options.store_team_position.promotor'),
                                'Kasir'       => __('lead::filament/resources/lead.options.store_team_position.kasir'),
                                'Frontliner'  => __('lead::filament/resources/lead.options.store_team_position.frontliner'),
                            ])
                            ->required()
                            ->disabled(fn (?string $operation = null, mixed $livewire = null): bool => static::shouldDisableUntilWhatsAppValidation($operation, $livewire))
                            ->inline(),
                        Forms\Components\Select::make('store_branch')
                            ->label(__('lead::filament/resources/lead.form.sections.store_information.fields.store_branch'))
                            ->searchable()
                            ->required()
                            ->disabled(fn (?string $operation = null, mixed $livewire = null): bool => static::shouldDisableUntilWhatsAppValidation($operation, $livewire))
                            ->options(Lead::storeBranchOptions())
                            ->placeholder(__('lead::filament/resources/lead.form.placeholders.store_branch')),
                        Forms\Components\Select::make('phone_transaction_range')
                            ->label(__('lead::filament/resources/lead.form.sections.store_information.fields.phone_transaction_range'))
                            ->placeholder(__('lead::filament/resources/lead.form.placeholders.phone_transaction_range'))
                            ->searchable()
                            ->disabled(fn (?string $operation = null, mixed $livewire = null): bool => static::shouldDisableUntilWhatsAppValidation($operation, $livewire))
                            ->options([
                                'Harga di bawah 2 juta' => __('lead::filament/resources/lead.options.phone_transaction_range.below_2m'),
                                'Harga 2 - 3 juta'      => __('lead::filament/resources/lead.options.phone_transaction_range.2m_3m'),
                                'Harga 3 - 4 juta'      => __('lead::filament/resources/lead.options.phone_transaction_range.3m_4m'),
                                'Harga 4 - 7 juta'      => __('lead::filament/resources/lead.options.phone_transaction_range.4m_7m'),
                                'Harga di atas 7 juta'  => __('lead::filament/resources/lead.options.phone_transaction_range.above_7m'),
                            ])
                            ->nullable()
                            ->required(fn (string $operation): bool => $operation === 'create'),
                        Forms\Components\Hidden::make('creator_id')
                            ->default(fn (): ?int => Auth::id()),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return LeadTable::table($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListLeads::route('/'),
            'create' => Pages\CreateLead::route('/create'),
            'edit'   => Pages\EditLead::route('/{record}/edit'),
        ];
    }

    protected static function getWhatsAppValidationHelperText(?string $operation, mixed $livewire): ?string
    {
        if ($operation !== 'create' || ! is_object($livewire) || ! method_exists($livewire, 'getWhatsAppValidationHelperText')) {
            return null;
        }

        return $livewire->getWhatsAppValidationHelperText();
    }

    protected static function checkWhatsAppValidation(mixed $livewire): mixed
    {
        if (! is_object($livewire) || ! method_exists($livewire, 'checkWhatsAppValidation')) {
            return null;
        }

        return $livewire->checkWhatsAppValidation();
    }

    protected static function shouldShowWhatsAppValidationAction(?string $operation, mixed $livewire): bool
    {
        return $operation === 'create'
            && is_object($livewire)
            && method_exists($livewire, 'isWhatsAppValidationEnabled')
            && $livewire->isWhatsAppValidationEnabled();
    }

    protected static function shouldDisableUntilWhatsAppValidation(?string $operation, mixed $livewire): bool
    {
        return $operation === 'create'
            && is_object($livewire)
            && method_exists($livewire, 'shouldDisableUntilWhatsAppValidation')
            && $livewire->shouldDisableUntilWhatsAppValidation();
    }
}
