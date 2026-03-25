<?php

namespace Cesa\Helpdesk\Filament\Resources\TicketResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TicketHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'histories';

    protected static ?string $title = null;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('helpdesk::filament/resources/ticket/relation-managers/ticket-histories.title');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('helpdesk::filament/resources/ticket/relation-managers/ticket-histories.columns.user'))
                    ->placeholder(__('helpdesk::filament/resources/ticket/relation-managers/ticket-histories.placeholders.dash')),
                Tables\Columns\TextColumn::make('ticketStatus.name')
                    ->label(__('helpdesk::filament/resources/ticket/relation-managers/ticket-histories.columns.status')),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('helpdesk::filament/resources/ticket/relation-managers/ticket-histories.columns.changed_at'))
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated(false);
    }
}
