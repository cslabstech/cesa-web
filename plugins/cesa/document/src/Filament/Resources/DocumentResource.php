<?php

namespace Cesa\Document\Filament\Resources;

use Cesa\Document\Filament\Resources\Document\Pages;
use Cesa\Document\Filament\Resources\Document\Tables\DocumentTable;
use Cesa\Document\Models\Document;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Webkul\PluginManager\Package;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static string|\BackedEnum|null $navigationIcon = null;

    protected static ?int $navigationSort = 15;

    public static function shouldRegisterNavigation(): bool
    {
        return Package::isPluginInstalled('document');
    }

    public static function getNavigationLabel(): string
    {
        return __('document::filament/resources/document.navigation.title');
    }

    public static function getPluralModelLabel(): string
    {
        return __('document::filament/resources/document.plural');
    }

    public static function getModelLabel(): string
    {
        return __('document::filament/resources/document.singular');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.document');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('document::filament/resources/document.form.sections.basic_information.title'))
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label(__('document::filament/resources/document.form.sections.basic_information.fields.title'))
                            ->required()
                            ->maxLength(255)
                            ->placeholder(__('document::filament/resources/document.form.placeholders.title')),

                        Forms\Components\Select::make('source_type')
                            ->label(__('document::filament/resources/document.form.sections.basic_information.fields.source_type'))
                            ->options([
                                'html' => __('document::filament/resources/document.options.source_type.html'),
                                'docx' => __('document::filament/resources/document.options.source_type.docx'),
                            ])
                            ->required()
                            ->live(),
                    ]),

                Section::make(__('document::filament/resources/document.form.sections.content.title'))
                    ->schema([
                        Forms\Components\FileUpload::make('docx_path')
                            ->label(__('document::filament/resources/document.form.sections.content.fields.docx_file'))
                            ->disk('local')
                            ->directory('documents')
                            ->openable()
                            ->downloadable()
                            ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                            ->visible(fn ($get) => $get('source_type') === 'docx')
                            ->columnSpanFull(),

                        Forms\Components\RichEditor::make('content')
                            ->toolbarButtons([
                                ['h1', 'h2', 'h3'],
                                ['bold', 'italic', 'underline', 'strike', 'clearFormatting'],
                                ['alignStart', 'alignCenter', 'alignEnd', 'alignJustify'],
                                ['bulletList', 'orderedList'],
                                ['link', 'horizontalRule', 'table'],
                                ['undo', 'redo'],
                            ])
                            ->label(__('document::filament/resources/document.form.sections.content.fields.html_content'))
                            ->visible(fn ($get) => $get('source_type') === 'html')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return DocumentTable::table($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDocuments::route('/'),
            'create' => Pages\CreateDocument::route('/create'),
            'edit'   => Pages\EditDocument::route('/{record}/edit'),
        ];
    }
}
