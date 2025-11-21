<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class BuildVersionCommand extends Command
{
    protected $signature = 'app:build-version';

    protected $description = 'Generate build version information for deployments.';

    public function handle(): int
    {
        $shortCommit = trim((string) shell_exec('git rev-parse --short HEAD'));
        if ($shortCommit === '') {
            $shortCommit = 'unknown';
        }

        $version = sprintf('%s-%s', now()->format('Ymd'), $shortCommit);

        $this->writeStorageVersion($version);
        $this->writeConfigVersion($version);

        $this->info("Build version generated: {$version}");

        return self::SUCCESS;
    }

    protected function writeStorageVersion(string $version): void
    {
        $payload = [
            'version' => $version,
            'generated_at' => Carbon::now()->toIso8601String(),
        ];

        Storage::disk('local')->put('build_version.json', json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    protected function writeConfigVersion(string $version): void
    {
        $configContent = <<<PHP
<?php

return [
    'build' => '{$version}',
];
PHP;

        file_put_contents(config_path('version.php'), $configContent);
    }
}
