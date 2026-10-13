<?php

declare(strict_types=1);

namespace App\Filament\Resources\ContactMessages\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class ContactMessageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make('Message')->columnSpan(2)->schema([
                TextEntry::make('message')->hiddenLabel()->prose(), // escaped by default; no HTML rendering
            ]),
            Section::make('Sender')->columnSpan(1)->schema([
                TextEntry::make('name'),
                TextEntry::make('email')->copyable(),
                TextEntry::make('inquiry_type')->label('Type')->badge(),
                TextEntry::make('created_at')->label('Received')->dateTime(),
                TextEntry::make('ip_address')->label('IP')->placeholder('—'),
            ]),
        ]);
    }
}
