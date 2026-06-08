<?php

namespace Cesa\FormTransfer\Filament\Clusters\Configurations\Resources;

use Cesa\FormTransfer\Filament\Clusters\Configurations;
use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\PublicCategoryResource\Pages;
use Cesa\FormTransfer\Models\FormTransferPublicCategory;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Security\Traits\HasResourcePermissionQuery;

class PublicCategoryResource extends Resource
{
    use HasResourcePermissionQuery;

    protected static ?string $model = FormTransferPublicCategory::class;

    protected static ?string $cluster = Configurations::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static ?int $navigationSort = 105;

    public static function getNavigationLabel(): string
    {
        return __('form-transfer::filament/clusters/configurations/resources/public-category.navigation.label');
    }

    public static function getNavigationGroup(): string
    {
        return __('form-transfer::filament/clusters/configurations/resources/public-category.navigation.group');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label(__('form-transfer::filament/clusters/configurations/resources/public-category.fields.name'))
                ->required()
                ->maxLength(191)
                ->live(onBlur: true)
                ->afterStateUpdated(function (Set $set, ?string $state, ?FormTransferPublicCategory $record): void {
                    if ($record?->exists && filled($record->slug)) {
                        return;
                    }

                    $set('slug', FormTransferPublicCategory::normalizeSlug($state));
                })
                ->columnSpanFull(),
            TextInput::make('slug')
                ->label(__('form-transfer::filament/clusters/configurations/resources/public-category.fields.slug'))
                ->required()
                ->maxLength(191)
                ->helperText(__('form-transfer::filament/clusters/configurations/resources/public-category.fields.slug_helper'))
                ->disabled(fn (?FormTransferPublicCategory $record): bool => (bool) $record?->isBuiltIn())
                ->dehydrateStateUsing(fn (?string $state): string => FormTransferPublicCategory::normalizeSlug($state))
                ->rules([
                    static fn (): \Closure => static function (string $attribute, mixed $value, \Closure $fail): void {
                        if (! FormTransferPublicCategory::isAllowedSlug($value)) {
                            $fail(__('form-transfer::filament/clusters/configurations/resources/public-category.validation.slug'));
                        }
                    },
                    static fn (?FormTransferPublicCategory $record): \Closure => static function (string $attribute, mixed $value, \Closure $fail) use ($record): void {
                        $normalizedSlug = FormTransferPublicCategory::normalizeSlug($value);

                        if ($normalizedSlug === '') {
                            return;
                        }

                        $slugAlreadyExists = FormTransferPublicCategory::withTrashed()
                            ->where('slug', $normalizedSlug)
                            ->when(
                                $record?->exists,
                                fn (Builder $query): Builder => $query->whereKeyNot($record->getKey()),
                            )
                            ->exists();

                        if ($slugAlreadyExists) {
                            $fail(__('form-transfer::filament/clusters/configurations/resources/public-category.validation.slug_unique'));
                        }
                    },
                ])
                ->columnSpanFull(),
            Toggle::make('is_active')
                ->label(__('form-transfer::filament/clusters/configurations/resources/public-category.fields.is_active'))
                ->disabled(fn (?FormTransferPublicCategory $record): bool => (bool) $record?->isBuiltIn())
                ->default(true),
            Textarea::make('description')
                ->label(__('form-transfer::filament/clusters/configurations/resources/public-category.fields.description'))
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('form-transfer::filament/clusters/configurations/resources/public-category.columns.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label(__('form-transfer::filament/clusters/configurations/resources/public-category.columns.slug'))
                    ->formatStateUsing(fn (string $state): string => '/form/'.$state)
                    ->copyable()
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label(__('form-transfer::filament/clusters/configurations/resources/public-category.columns.is_active'))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('form-transfer::filament/clusters/configurations/resources/public-category.filters.is_active')),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('openPublicForm')
                    ->label(__('form-transfer::filament/clusters/configurations/resources/public-category.actions.open_form'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (FormTransferPublicCategory $record): string => route('form-transfer.public.dynamic-index', [
                        'publicIndexSlug' => $record->slug,
                    ]))
                    ->hidden(fn (FormTransferPublicCategory $record): bool => ! $record->is_active),
                EditAction::make()->slideOver()->modalWidth('md'),
                DeleteAction::make()
                    ->hidden(fn (FormTransferPublicCategory $record): bool => $record->isBuiltIn()),
                RestoreAction::make(),
                ForceDeleteAction::make()
                    ->hidden(fn (FormTransferPublicCategory $record): bool => $record->isBuiltIn()),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->authorizeIndividualRecords('delete'),
                RestoreBulkAction::make(),
                ForceDeleteBulkAction::make()
                    ->authorizeIndividualRecords('forceDelete'),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPublicCategories::route('/'),
        ];
    }
}
