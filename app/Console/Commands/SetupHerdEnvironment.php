<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SetupHerdEnvironment extends Command
{
    protected $signature = 'app:setup-herd {--skip-migrate : Skip database migrations}';

    protected $description = 'Prepare local Herd environment (env, key, migrations, Passport, storage link)';

    public function handle(): int
    {
        $this->line('Preparing Task Manager for Herd...');

        $this->ensureEnvironmentFile();
        $this->ensureAppKey();
        $this->ensureStorageLink();

        if (!$this->option('skip-migrate')) {
            $this->call('migrate', ['--force' => true]);
        }

        $this->ensurePassportKeys();
        $this->ensurePassportPersonalClient();

        $this->newLine();
        $this->info('Herd setup is complete.');
        $this->line('Next steps: run `npm --prefix frontend install` and `npm --prefix frontend run dev`.');

        return self::SUCCESS;
    }

    private function ensureEnvironmentFile(): void
    {
        $envPath = base_path('.env');

        if (file_exists($envPath)) {
            $this->line('• .env already exists');
            return;
        }

        copy(base_path('.env.example'), $envPath);
        $this->info('• Created .env from .env.example');
    }

    private function ensureAppKey(): void
    {
        if (!empty(config('app.key'))) {
            $this->line('• APP_KEY already set');
            return;
        }

        $this->call('key:generate', ['--force' => true]);
    }

    private function ensureStorageLink(): void
    {
        $storagePath = public_path('storage');

        if (is_link($storagePath) || file_exists($storagePath)) {
            $this->line('• Storage link already present');
            return;
        }

        $this->call('storage:link');
    }

    private function ensurePassportKeys(): void
    {
        $privateKey = storage_path('oauth-private.key');
        $publicKey = storage_path('oauth-public.key');

        if (file_exists($privateKey) && file_exists($publicKey)) {
            $this->line('• Passport keys already present');
            return;
        }

        $this->call('passport:keys', ['--force' => true]);
    }

    private function ensurePassportPersonalClient(): void
    {
        if (!Schema::hasTable('oauth_clients')) {
            $this->warn('• Passport client table not found yet. Run migrations first to create OAuth tables.');
            return;
        }

        if (DB::table('oauth_clients')->where('name', 'Task Manager Personal Access Client')->exists()) {
            $this->line('• Passport personal access client already exists');
            return;
        }

        $this->call('passport:client', [
            '--personal' => true,
            '--name' => 'Task Manager Personal Access Client',
            '--provider' => config('auth.defaults.provider', 'users'),
            '--no-interaction' => true,
        ]);
    }
}
