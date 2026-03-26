<?php

namespace Cesa\Document\Filament\Resources\Document\Tables;

use Cesa\Document\Models\Document;
use Cesa\Document\Services\DocumentExportService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;

class DocumentTable
{
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label(__('document::filament/resources/document.table.columns.title'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('source_type')
                    ->label(__('document::filament/resources/document.table.columns.source_type'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'html'  => 'success',
                        'docx'  => 'primary',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('document::filament/resources/document.table.columns.created_at'))
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('document::filament/resources/document.table.columns.updated_at'))
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Add filters here if needed
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('downloadExcelTemplate')
                    ->label(__('document::filament/resources/document.actions.download_excel_template'))
                    ->icon('heroicon-m-document-arrow-down')
                    ->action(function (Document $record) {
                        try {
                            return app(DocumentExportService::class)->excelTemplate($record);
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title(__('document::filament/resources/document.notifications.template_error.title'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();

                            return null;
                        }
                    }),
                Action::make('download')
                    ->label(__('document::filament/resources/document.actions.download_word'))
                    ->icon('heroicon-m-arrow-down-tray')
                    ->slideOver()
                    ->form(function (Document $record) {
                        $placeholders = [];
                        try {
                            $placeholders = app(DocumentExportService::class)->detectPlaceholders($record);
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title(__('document::filament/resources/document.notifications.placeholder_error.title'))
                                ->body($e->getMessage())
                                ->warning()
                                ->send();
                        }

                        return [
                            Forms\Components\TextInput::make('filename')
                                ->label(__('document::filament/resources/document.fields.filename'))
                                ->placeholder(__('document::filament/resources/document.placeholders.filename'))
                                ->helperText(__('document::filament/resources/document.helpers.filename'))
                                ->columnSpanFull(),

                            Forms\Components\ToggleButtons::make('mode')
                                ->label(__('document::filament/resources/document.fields.mode'))
                                ->options([
                                    'single' => __('document::filament/resources/document.options.mode.single'),
                                    'bulk'   => __('document::filament/resources/document.options.mode.bulk'),
                                ])
                                ->default('single')
                                ->live()
                                ->inline(),

                            Forms\Components\Repeater::make('values')
                                ->label(__('document::filament/resources/document.fields.key_value'))
                                ->schema([
                                    Forms\Components\TextInput::make('key')
                                        ->label(__('document::filament/resources/document.fields.key'))
                                        ->readonly()
                                        ->required(),
                                    Forms\Components\TextInput::make('value')
                                        ->label(__('document::filament/resources/document.fields.value'))
                                        ->required(),
                                ])
                                ->grid(2)
                                ->addable(false)
                                ->deletable(false)
                                ->reorderable(false)
                                ->default(! empty($placeholders)
                                    ? array_map(fn ($k) => ['key' => $k, 'value' => ''], $placeholders)
                                    : [['key' => '', 'value' => '']]
                                )
                                ->hidden(fn ($get) => ($get('mode') ?? 'single') !== 'single'),

                            Forms\Components\FileUpload::make('excel')
                                ->label(__('document::filament/resources/document.fields.upload_excel'))
                                ->acceptedFileTypes([
                                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                    'application/vnd.ms-excel',
                                ])
                                ->disk('local')
                                ->directory('temp/placeholders')
                                ->preserveFilenames()
                                ->helperText(__('document::filament/resources/document.helpers.excel'))
                                ->hidden(fn ($get) => ($get('mode') ?? 'single') !== 'bulk'),
                        ];
                    })
                    ->action(function (array $data, Document $record) {
                        try {
                            return app(DocumentExportService::class)->download($record, $data);
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title(__('document::filament/resources/document.notifications.download_error.title'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();

                            return null;
                        }
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
