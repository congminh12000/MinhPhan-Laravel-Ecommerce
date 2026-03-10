<?php

use Database\Seeders\LocalSnapshotSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Secret-bearing settings should survive snapshot syncs.
     */
    private const SENSITIVE_ROWS = [
        ['type' => 'system', 'space' => 'base', 'name' => 'smtp'],
        ['type' => 'system', 'space' => 'base', 'name' => 'mailgun'],
        ['type' => 'system', 'space' => 'base', 'name' => 'sendmail'],
        ['type' => 'plugin', 'space' => 'sepay', 'name' => 'secret_key'],
        ['type' => 'plugin', 'space' => 'rate', 'name' => 'rate_api_key'],
    ];

    public function up(): void
    {
        $preservedRows = [];

        foreach (self::SENSITIVE_ROWS as $key) {
            $row = DB::table('settings')
                ->where('type', $key['type'])
                ->where('space', $key['space'])
                ->where('name', $key['name'])
                ->first();

            if ($row) {
                $preservedRows[] = (array) $row;
            }
        }

        (new LocalSnapshotSeeder())->run();

        foreach ($preservedRows as $row) {
            DB::table('settings')
                ->where('type', $row['type'])
                ->where('space', $row['space'])
                ->where('name', $row['name'])
                ->update([
                    'value'      => $row['value'],
                    'json'       => $row['json'],
                    'updated_at' => $row['updated_at'],
                ]);
        }
    }

    public function down(): void
    {
        // One-way data sync to align existing environments with the local snapshot.
    }
};
