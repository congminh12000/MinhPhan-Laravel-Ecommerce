<?php

namespace Tests\Unit;

use Tests\TestCase;

class VietnameseLanguageParityTest extends TestCase
{
    public function test_vietnamese_language_tree_matches_english_structure(): void
    {
        $englishFiles = $this->languageFileList(resource_path('lang/en'));
        $vietnameseFiles = $this->languageFileList(resource_path('lang/vi'));

        $this->assertSame($englishFiles, $vietnameseFiles);
    }

    private function languageFileList(string $directory): array
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
        );

        return collect(iterator_to_array($iterator))
            ->filter(fn (\SplFileInfo $file) => $file->isFile() && $file->getExtension() === 'php')
            ->map(fn (\SplFileInfo $file) => str_replace($directory . '/', '', $file->getPathname()))
            ->sort()
            ->values()
            ->all();
    }
}
