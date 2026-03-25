<?php

namespace Cesa\Helpdesk\Filament\Resources\TicketResource\RelationManagers;

use Cesa\Helpdesk\Models\Comment;
use Cesa\Helpdesk\Services\TicketCommentService;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Webkul\Security\Models\User;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    protected static ?string $title = null;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('helpdesk::filament/resources/ticket/relation-managers/comments.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                RichEditor::make('comment')
                    ->required()
                    ->columnSpanFull(),
                Select::make('visibility')
                    ->label(__('helpdesk::filament/resources/ticket/relation-managers/comments.form.fields.visibility'))
                    ->options([
                        Comment::VISIBILITY_PUBLIC   => __('helpdesk::filament/resources/ticket/relation-managers/comments.form.options.public_comment'),
                        Comment::VISIBILITY_INTERNAL => __('helpdesk::filament/resources/ticket/relation-managers/comments.form.options.internal_note'),
                    ])
                    ->default(Comment::VISIBILITY_PUBLIC)
                    ->visible(fn (): bool => Gate::allows('addInternalNote', $this->ownerRecord)),
                FileUpload::make('attachments')
                    ->label(__('helpdesk::filament/resources/ticket/relation-managers/comments.form.fields.attachments'))
                    ->multiple()
                    ->disk(config('helpdesk.attachments.comment.disk'))
                    ->directory(config('helpdesk.attachments.comment.directory'))
                    ->visibility(config('helpdesk.attachments.comment.visibility'))
                    ->maxSize(config('helpdesk.attachments.comment.max_size'))
                    ->maxFiles(config('helpdesk.attachments.comment.max_files'))
                    ->downloadable()
                    ->openable()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): Builder {
                if (Gate::allows('viewInternalNotes', $this->ownerRecord)) {
                    return $query;
                }

                return $query->where('visibility', Comment::VISIBILITY_PUBLIC);
            })
            ->headerActions([
                CreateAction::make()
                    ->icon('heroicon-o-plus-circle')
                    ->visible(fn (): bool => Gate::allows('comment', $this->ownerRecord))
                    ->using(function (array $data, RelationManager $livewire, TicketCommentService $ticketCommentService): Comment {
                        return $ticketCommentService->create(
                            $this->resolveAuthenticatedUser(),
                            $livewire->ownerRecord,
                            $data,
                        );
                    }),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('helpdesk::filament/resources/ticket/relation-managers/comments.table.columns.user'))
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('visibility')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === Comment::VISIBILITY_INTERNAL
                        ? __('helpdesk::filament/resources/ticket/relation-managers/comments.table.visibility.internal')
                        : __('helpdesk::filament/resources/ticket/relation-managers/comments.table.visibility.public'))
                    ->color(fn (string $state): string => $state === Comment::VISIBILITY_INTERNAL ? 'warning' : 'success'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('helpdesk::filament/resources/ticket/relation-managers/comments.table.columns.created_at'))
                    ->dateTime('d M Y H:i')
                    ->color('gray'),
                Tables\Columns\TextColumn::make('comment')
                    ->html()
                    ->wrap(),
                Tables\Columns\TextColumn::make('attachments')
                    ->label(__('helpdesk::filament/resources/ticket/relation-managers/comments.table.columns.attachments'))
                    ->formatStateUsing(fn (?array $state): string => $state ? (string) count($state) : __('helpdesk::filament/resources/ticket/relation-managers/comments.table.placeholders.zero')),
            ])
            ->recordActions([
                EditAction::make()
                    ->slideOver()
                    ->visible(fn (Comment $record): bool => Gate::allows('update', $record)),
            ])
            ->paginated(false)
            ->defaultSort('created_at', 'desc');
    }

    protected function resolveAuthenticatedUser(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403, __('helpdesk::filament/resources/ticket/relation-managers/comments.errors.invalid_user'));

        return $user;
    }
}
