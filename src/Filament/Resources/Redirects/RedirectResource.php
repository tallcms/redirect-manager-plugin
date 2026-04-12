<?php

namespace Tallcms\RedirectManager\Filament\Resources\Redirects;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Tallcms\RedirectManager\Filament\Resources\Redirects\Pages\CreateRedirect;
use Tallcms\RedirectManager\Filament\Resources\Redirects\Pages\EditRedirect;
use Tallcms\RedirectManager\Filament\Resources\Redirects\Pages\ListRedirects;
use Tallcms\RedirectManager\Filament\Resources\Redirects\Schemas\RedirectForm;
use Tallcms\RedirectManager\Filament\Resources\Redirects\Tables\RedirectsTable;
use Tallcms\RedirectManager\Models\Redirect;

class RedirectResource extends Resource
{
    protected static ?string $model = Redirect::class;

    protected static ?string $pluralModelLabel = 'Redirects';

    public static function form(Schema $schema): Schema
    {
        return RedirectForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RedirectsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRedirects::route('/'),
            'create' => CreateRedirect::route('/create'),
            'edit' => EditRedirect::route('/{record}/edit'),
        ];
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-arrow-right-circle';
    }

    public static function getNavigationGroup(): ?string
    {
        return config('tallcms.navigation.groups.configuration', 'Configuration');
    }

    public static function getNavigationLabel(): string
    {
        return 'Redirects';
    }

    public static function getNavigationSort(): ?int
    {
        return 45;
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) Redirect::active()->count() ?: null;
    }
}
