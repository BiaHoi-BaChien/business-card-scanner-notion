<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

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

        $stored = $this->readStoredVersion();
        if ($stored !== null) {
            return $stored;
        }

        $gitVersion = $this->generateGitVersion();
        if ($gitVersion !== null) {
            return $gitVersion;
        }

        return 'dev';
    }

    protected function readStoredVersion(): ?string
    {
        $storagePath = storage_path('app/build_version.json');
        if (file_exists($storagePath)) {
            $payload = json_decode((string) file_get_contents($storagePath), true);
            if (is_array($payload) && isset($payload['version']) && is_string($payload['version'])) {
                return $payload['version'];
            }
        }

        return null;
    }

    protected function generateGitVersion(): ?string
    {
        $shortCommit = trim((string) shell_exec('git rev-parse --short HEAD'));
        if ($shortCommit === '') {
            return null;
        }

        return sprintf('%s-%s', Carbon::now()->format('Ymd'), $shortCommit);
    }
}
