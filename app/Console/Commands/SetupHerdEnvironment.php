<?php

namespace App\Console\Commands;

use Throwable;
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
        $this->ensureSqliteDatabaseFile();

        if (!$this->option('skip-migrate')) {
            if (!$this->runMigrationsWithFallback()) {
                return self::FAILURE;
            }
        }

        $this->ensurePassportKeys();
        $this->ensurePassportPersonalClient();
        $this->ensureSeedData();

        $this->newLine();
        $this->info('Herd setup is complete.');
        $this->line('Next steps: run `npm --prefix frontend install` and `npm --prefix frontend run dev`.');

        return self::SUCCESS;
    }

    private function runMigrationsWithFallback(): bool
    {
        try {
            return $this->call('migrate', ['--force' => true]) === self::SUCCESS;
        } catch (Throwable $exception) {
            if (!$this->isMySqlConnectionRefused($exception) || !$this->fallbackToSqlite()) {
                throw $exception;
            }

            return $this->call('migrate', ['--force' => true]) === self::SUCCESS;
        }
    }

    private function isMySqlConnectionRefused(Throwable $exception): bool
    {
        if (config('database.default') !== 'mysql') {
            return false;
        }

        $message = $exception->getMessage();

        return str_contains($message, 'SQLSTATE[HY000] [2002]')
            || str_contains($message, 'Connection refused')
            || str_contains($message, 'actively refused');
    }

    private function fallbackToSqlite(): bool
    {
        $databasePath = database_path('database.sqlite');

        if (!file_exists($databasePath)) {
            touch($databasePath);
        }

        $updated = $this->replaceEnvValue('DB_CONNECTION', 'sqlite');
        $updated = $this->replaceEnvValue('DB_DATABASE', 'database/database.sqlite') || $updated;

        if (!$updated) {
            $this->warn('• Could not update .env for SQLite fallback. Please set DB_CONNECTION=sqlite manually.');
            return false;
        }

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => $databasePath,
        ]);

        DB::purge();

        $this->warn('• MySQL is unreachable. Falling back to SQLite for local one-click setup.');
        $this->line('• Updated .env: DB_CONNECTION=sqlite, DB_DATABASE=database/database.sqlite');

        return true;
    }

    private function replaceEnvValue(string $key, string $value): bool
    {
        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            return false;
        }

        $env = file_get_contents($envPath);

        if ($env === false) {
            return false;
        }

        $pattern = "/^{$key}=.*$/m";

        if (preg_match($pattern, $env) === 1) {
            $updated = preg_replace($pattern, "{$key}={$value}", $env, 1);

            if ($updated === null) {
                return false;
            }

            return file_put_contents($envPath, $updated) !== false;
        }

        $separator = str_ends_with($env, PHP_EOL) ? '' : PHP_EOL;

        return file_put_contents($envPath, $env.$separator."{$key}={$value}".PHP_EOL) !== false;
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

    private function ensureSqliteDatabaseFile(): void
    {
        if (config('database.default') !== 'sqlite') {
            return;
        }

        $configuredPath = config('database.connections.sqlite.database');
        $databasePath = is_string($configuredPath) && $configuredPath !== ''
            ? $configuredPath
            : database_path('database.sqlite');

        if (!str_starts_with($databasePath, DIRECTORY_SEPARATOR)
            && !preg_match('/^[A-Za-z]:\\\\/', $databasePath)
        ) {
            $databasePath = base_path($databasePath);
        }

        if (file_exists($databasePath)) {
            return;
        }

        $directory = dirname($databasePath);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        touch($databasePath);
        $this->line('• Created SQLite database file at '.str_replace('\\', '/', $databasePath));
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
        try {
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
        } catch (Throwable $exception) {
            if ($this->isMySqlConnectionRefused($exception)) {
                $this->warn('• Skipping Passport personal client creation because MySQL is unreachable.');
                return;
            }

            throw $exception;
        }
    }

    private function ensureSeedData(): void
    {
        try {
            if (!Schema::hasTable('users')) {
                $this->warn('• Users table not found yet. Run migrations first to seed demo accounts.');
                return;
            }

            $hasAdminSeedUser = DB::table('users')->where('email', 'admin@example.com')->exists();
            $hasStandardSeedUser = DB::table('users')->where('email', 'user@example.com')->exists();

            if ($hasAdminSeedUser || $hasStandardSeedUser) {
                $this->line('• Seed data already present (admin/user test accounts detected)');
                return;
            }

            $this->call('db:seed', ['--force' => true]);
        } catch (Throwable $exception) {
            if ($this->isMySqlConnectionRefused($exception)) {
                $this->warn('• Skipping database seeding because MySQL is unreachable.');
                return;
            }

            throw $exception;
        }
    }
}
