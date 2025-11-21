<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $version = $this->resolveBuildVersion();
        config(['version.build' => $version]);

        view()->share('buildVersion', $version);
    }

    protected function resolveBuildVersion(): string
    {
        $configured = config('version.build');
        if (is_string($configured) && Str::of($configured)->trim()->isNotEmpty()) {
            return (string) $configured;
        }

        $storagePath = storage_path('app/build_version.json');
        if (file_exists($storagePath)) {
            $payload = json_decode((string) file_get_contents($storagePath), true);
            if (is_array($payload) && isset($payload['version']) && is_string($payload['version'])) {
                return $payload['version'];
            }
        }

        return 'dev';
    }
}
