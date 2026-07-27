<?php

namespace App\Console\Commands;

use App\Models\Core\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class SyncSettingsFromConfig extends Command
{
    protected $signature = 'myfinance:sync-settings';
    protected $description = 'Ajoute en base les settings définis dans config/myfinance-settings.php absents';

    public function handle(): int
    {
        $configSettings = Arr::dot(config('myfinance-settings'));

        $created = 0;
        $skipped = 0;

        foreach ($this->flattenConfig() as $key => $meta) {
            $exists = Setting::where('key', $key)->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            Setting::create([
                'key' => $key,
                'value' => $meta['default'],
                'type' => $meta['type'],
                'label' => $meta['label'] ?? null,
            ]);

            $created++;
        }

        $this->info("✅ {$created} nouveau(x) setting(s) ajouté(s), {$skipped} déjà existant(s) (non touchés).");
        return self::SUCCESS;
    }

    protected function flattenConfig(): array
    {
        $result = [];

        foreach (config('myfinance-settings') as $group => $groupData) {
            // On ignore 'label' du groupe, on descend directement dans 'settings'
            foreach ($groupData['settings'] ?? [] as $key => $meta) {
                $result[$key] = $meta;
            }
        }

        return $result;
    }
}