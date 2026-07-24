<?php

namespace App\Filament\Pages\Core;

use App\Services\SettingsManager;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Arr;
use UnitEnum;

class SettingsPage extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static string|UnitEnum|null $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Parametres';
    protected static ?string $title = 'Parametres systeme';
    protected string $view = 'filament.pages.core.settings-page';

    public static function getNavigationLabel(): string
    {
        return __('myfinance.settings');
    }

    public static function getNavigationGroup(): string
    {
        return __('myfinance.settings');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('settings.view') ?? false;
    }

    public ?array $data = [];

    public function mount(): void
    {
        $manager = app(SettingsManager::class);
        $initial = [];

        foreach ($manager->registry() as $groupKey => $group) {
            if (! is_array($group) || ! isset($group['settings']) || ! is_array($group['settings'])) {
                continue;
            }
            foreach ($group['settings'] as $key => $def) {
                // data_set gere nativement la notation a points et cree
                // automatiquement la structure imbriquee attendue par Filament
                data_set($initial, $key, $manager->get($key));
            }
        }

        $this->form->fill($initial);
    }

    public function form(Schema $schema): Schema
    {
        $manager = app(SettingsManager::class);
        $tabs = [];

        foreach ($manager->registry() as $groupKey => $group) {
            if (! is_array($group) || ! isset($group['settings']) || ! is_array($group['settings'])) {
                continue;
            }

            $fields = [];
            foreach ($group['settings'] as $key => $def) {
                $fields[] = $this->buildField($key, $def);
            }

            $tabs[] = Tab::make($group['label'] ?? $groupKey)->schema($fields);
        }

        return $schema
            ->components([
                Tabs::make('settings-tabs')->tabs($tabs),
            ])
            ->statePath('data');
    }

    protected function buildField(string $key, array $def)
    {
        $options = null;

        if (isset($def['options_resolver'])) {
            $options = \App\Services\SettingsOptionsResolver::resolve($def['options_resolver']);
        } elseif (isset($def['options'])) {
            $options = $def['options'];
        }

        $field = match ($def['type'] ?? 'text') {
            'boolean' => Toggle::make($key),
            'integer', 'decimal' => TextInput::make($key)->numeric(),
            'select' => Select::make($key)->options($options ?? []),
            'multiselect' => Select::make($key)->multiple()->options($options ?? []),
            default => TextInput::make($key),
        };

        $field->label($def['label'] ?? $key);

        if (isset($def['visible_when'])) {
            $field->visible(fn (Get $get) => (bool) $get($def['visible_when']));
        }

        return $field;
    }

    public function save(): void
    {
        if (! auth()->user()?->can('settings.update')) {
            Notification::make()->title("Vous n'avez pas le droit de modifier les parametres.")->danger()->send();
            return;
        }

        $manager = app(SettingsManager::class);
        $state = $this->form->getState(); // tableau imbrique

        // Arr::dot() aplatit { security: { 2fa: true } } en 'security.2fa' => true
        foreach (Arr::dot($state) as $key => $value) {
            $manager->set($key, $value);
        }

        Notification::make()->title('Parametres enregistres.')->success()->send();
    }
}