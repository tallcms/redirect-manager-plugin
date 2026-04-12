<?php

namespace Tallcms\RedirectManager\Filament\Resources\Redirects\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Tallcms\RedirectManager\Models\Redirect;
use Tallcms\RedirectManager\Rules\NoSelfRedirect;
use Tallcms\RedirectManager\Rules\UniqueSourcePath;

class RedirectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('source_path')
                    ->label('Source Path')
                    ->required()
                    ->maxLength(2048)
                    ->placeholder('/old-page')
                    ->helperText('The path to redirect from (e.g., /old-page). Query strings are ignored.')
                    ->rules([
                        'starts_with:/',
                        new UniqueSourcePath,
                    ])
                    ->columnSpanFull(),

                TextInput::make('destination_url')
                    ->label('Destination URL')
                    ->required()
                    ->maxLength(2048)
                    ->placeholder('/new-page or https://example.com/page')
                    ->helperText('The URL to redirect to — can be a path or full URL')
                    ->rules([
                        new NoSelfRedirect,
                    ])
                    ->columnSpanFull(),

                Select::make('status_code')
                    ->label('Status Code')
                    ->options([
                        301 => 'Permanent (301)',
                        302 => 'Temporary (302)',
                    ])
                    ->default(301)
                    ->required()
                    ->helperText('301 for permanent moves (SEO-friendly), 302 for temporary'),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),

                Textarea::make('note')
                    ->label('Note')
                    ->maxLength(500)
                    ->placeholder('Why this redirect exists')
                    ->columnSpanFull(),
            ]);
    }
}
