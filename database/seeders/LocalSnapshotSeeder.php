<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class LocalSnapshotSeeder extends Seeder
{
    private const TABLES = [
        'languages',
        'currencies',
        'customer_groups',
        'rma_reasons',
        'plugins',
        'settings',
        'brands',
        'categories',
        'category_descriptions',
        'category_paths',
        'attribute_groups',
        'attribute_group_descriptions',
        'attributes',
        'attribute_descriptions',
        'attribute_values',
        'attribute_value_descriptions',
        'page_categories',
        'page_category_descriptions',
        'pages',
        'page_descriptions',
        'page_products',
        'products',
        'product_descriptions',
        'product_skus',
        'product_categories',
        'product_relations',
        'product_attributes',
    ];

    public function run(): void
    {
        $snapshot = $this->loadSnapshot();

        Schema::disableForeignKeyConstraints();

        try {
            foreach (array_reverse(self::TABLES) as $table) {
                DB::table($table)->truncate();
            }

            foreach (self::TABLES as $table) {
                $rows = $snapshot[$table] ?? [];
                if (empty($rows)) {
                    continue;
                }

                foreach (array_chunk($rows, 200) as $chunk) {
                    DB::table($table)->insert($chunk);
                }
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    private function loadSnapshot(): array
    {
        $path = database_path('seeders/data/local_snapshot.json');

        if (! File::exists($path)) {
            throw new \RuntimeException("Local snapshot data file is missing: {$path}");
        }

        $snapshot = json_decode(File::get($path), true);
        if (! is_array($snapshot)) {
            throw new \RuntimeException("Local snapshot data file is invalid JSON: {$path}");
        }

        return $snapshot;
    }
}
