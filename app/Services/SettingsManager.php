<?php

namespace App\Services;

use App\Models\Core\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsManager
{
    protected array $registry;

    public function __construct()
    {
        $this->registry = config('myfinance-settings');
    }

    public function get(string $key, ?int $branchId = null, mixed $default = null): mixed
    {
        $all = $this->allCached();

        $value = null;
        $found = false;

        if ($branchId && isset($all['branch'][$branchId][$key])) {
            $value = $all['branch'][$branchId][$key];
            $found = true;
        } elseif (isset($all['global'][$key])) {
            $value = $all['global'][$key];
            $found = true;
        }

        if (! $found) {
            return $default ?? $this->definitionFor($key)['default'] ?? null;
        }

        return $this->castValue($key, $value);
    }

    protected function castValue(string $key, mixed $value): mixed
    {
        $def = $this->definitionFor($key);

        return match ($def['type'] ?? null) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'decimal' => (float) $value,
            'multiselect' => is_array($value) ? $value : (array) json_decode($value ?? '[]', true),
            default => $value,
        };
    }

    public function set(string $key, mixed $value, ?int $branchId = null): void
    {
        $def = $this->definitionFor($key);

        $value = match ($def['type'] ?? null) {
            'boolean' => (bool) $value,
            'integer' => (int) $value,
            'decimal' => (float) $value,
            default => $value,
        };

        Setting::updateOrCreate(
            ['key' => $key, 'branch_id' => $branchId],
            ['value' => $value, 'updated_by' => auth()->id()]
        );

        // activity('settings')
        //     ->causedBy(auth()->user())
        //     ->withProperties(['key' => $key, 'value' => $value, 'branch_id' => $branchId])
        //     ->log('setting_updated');

        Cache::forget('settings.all');
    }

    protected function allCached(): array
    {
        return Cache::rememberForever('settings.all', function () {
            $rows = Setting::all();
            $result = ['global' => [], 'branch' => []];

            foreach ($rows as $row) {
                if ($row->branch_id) {
                    $result['branch'][$row->branch_id][$row->key] = $row->value;
                } else {
                    $result['global'][$row->key] = $row->value;
                }
            }

            return $result;
        });
    }

    public function definitionFor(string $key): ?array
    {
        foreach ($this->registry as $group) {
            if (isset($group['settings'][$key])) {
                return $group['settings'][$key];
            }
        }
        return null;
    }

    public function registry(): array
    {
        return $this->registry;
    }
}