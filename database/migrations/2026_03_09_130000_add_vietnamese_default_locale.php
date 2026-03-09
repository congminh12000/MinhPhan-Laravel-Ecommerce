<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('languages')->updateOrInsert(
            ['code' => 'vi'],
            [
                'name' => 'Tiếng Việt',
                'locale' => 'vi_VN',
                'image' => 'catalog/favicon.png',
                'sort_order' => 1,
                'status' => 1,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        DB::table('settings')->updateOrInsert(
            ['type' => 'system', 'space' => 'base', 'name' => 'locale'],
            ['value' => 'vi', 'json' => 0]
        );

        DB::table('admin_users')->update(['locale' => 'vi']);
    }

    public function down(): void
    {
        DB::table('admin_users')->where('locale', 'vi')->update(['locale' => 'en']);

        DB::table('settings')
            ->where(['type' => 'system', 'space' => 'base', 'name' => 'locale'])
            ->update(['value' => 'en', 'json' => 0]);

        DB::table('languages')->where('code', 'vi')->delete();
    }
};
