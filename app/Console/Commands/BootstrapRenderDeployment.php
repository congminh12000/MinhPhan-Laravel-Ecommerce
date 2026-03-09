<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Beike\Admin\Repositories\AdminUserRepo;
use Database\Seeders\ThemeSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class BootstrapRenderDeployment extends Command
{
    private const REQUIRED_THEME_SETTINGS = [
        'menu_setting',
        'design_setting',
        'footer_setting',
    ];

    protected $signature = 'app:bootstrap-render';

    protected $description = 'Prepare the application for Render before the web server starts';

    public function handle(): int
    {
        $this->ensureSqliteDatabaseExists();

        $this->callSilently('migrate', ['--force' => true]);

        if ($this->shouldSeedDatabase()) {
            $this->info('Seeding initial application data...');
            $this->call('db:seed', ['--force' => true]);
        } else {
            $this->repairRequiredSettings();
        }

        if (DB::getDriverName() === 'pgsql') {
            $this->callSilently('postgreSQL:sequence');
        }

        $this->ensureAdminUserExists();
        $this->ensureInstalledMarkerExists();

        $this->info('Render bootstrap completed.');

        return self::SUCCESS;
    }

    private function ensureSqliteDatabaseExists(): void
    {
        if (config('database.default') !== 'sqlite') {
            return;
        }

        $databasePath = (string) config('database.connections.sqlite.database');

        if ($databasePath === '') {
            return;
        }

        $directory = dirname($databasePath);
        if (! is_dir($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        if (! file_exists($databasePath)) {
            File::put($databasePath, '');
            $this->info("Created SQLite database at {$databasePath}");
        }
    }

    private function shouldSeedDatabase(): bool
    {
        if (! Schema::hasTable('settings')) {
            return true;
        }

        if (DB::table('settings')->count() === 0) {
            return true;
        }

        if (! Schema::hasTable('admin_users')) {
            return true;
        }

        return DB::table('admin_users')->count() === 0;
    }

    private function repairRequiredSettings(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        DB::table('settings')->updateOrInsert(
            ['type' => 'system', 'space' => 'base', 'name' => 'locale'],
            ['value' => 'vi', 'json' => 0]
        );

        $missingThemeSettings = collect(self::REQUIRED_THEME_SETTINGS)
            ->filter(fn (string $name) => ! DB::table('settings')
                ->where('type', 'system')
                ->where('space', 'base')
                ->where('name', $name)
                ->exists())
            ->values();

        if ($missingThemeSettings->isEmpty()) {
            return;
        }

        $this->warn('Repairing missing theme settings: ' . $missingThemeSettings->implode(', '));
        $this->call('db:seed', ['--class' => ThemeSeeder::class, '--force' => true]);
    }

    private function ensureAdminUserExists(): void
    {
        if (! Schema::hasTable('admin_users') || DB::table('admin_users')->exists()) {
            return;
        }

        $email = (string) env('ADMIN_EMAIL', 'admin@example.com');
        $password = (string) env('ADMIN_PASSWORD', 'ChangeMe123!');

        AdminUserRepo::createAdminUser([
            'name' => strstr($email, '@', true) ?: 'admin',
            'email' => $email,
            'password' => $password,
            'locale' => 'vi',
            'roles' => [],
        ]);

        $this->warn("Created initial admin user for {$email}");
    }

    private function ensureInstalledMarkerExists(): void
    {
        $installedPath = storage_path('installed');

        if (! file_exists($installedPath)) {
            File::put($installedPath, 'Installed by Render bootstrap at ' . now()->toDateTimeString() . PHP_EOL);
        }
    }
}
