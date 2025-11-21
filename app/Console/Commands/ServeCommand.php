<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ServeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'serve {--host=127.0.0.1} {--port=8000}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Serve the application on the PHP development server';

    public function handle(): int
    {
        $host = (string) $this->option('host');
        $port = (string) $this->option('port');
        $publicPath = public_path();
        $router = base_path('server.php');

        $this->info("Laravel development server started on http://{$host}:{$port}/");

        $command = sprintf(
            'php -S %s -t %s %s',
            escapeshellarg("{$host}:{$port}"),
            escapeshellarg($publicPath),
            escapeshellarg($router)
        );

        passthru($command);

        return self::SUCCESS;
    }
}
