<?php

namespace Database\Seeders\Concerns;

trait SeedsVietnameseLocale
{
    protected function appendVietnameseLocaleRows(array $rows, string $sourceLocale = 'en', ?callable $transform = null): array
    {
        $nextId = collect($rows)->pluck('id')->filter()->max() ?? 0;
        $vietnameseRows = [];

        foreach ($rows as $row) {
            if (($row['locale'] ?? null) !== $sourceLocale) {
                continue;
            }

            $clone = $row;
            $clone['locale'] = 'vi';

            if (array_key_exists('id', $clone)) {
                $clone['id'] = ++$nextId;
            }

            if ($transform) {
                $clone = $transform($clone, $row);
            }

            $vietnameseRows[] = $clone;
        }

        return array_merge($rows, $vietnameseRows);
    }

    protected function addVietnameseLocaleToPayload(mixed $payload, array $sourcePriority = ['zh_cn', 'en']): mixed
    {
        if (! is_array($payload)) {
            return $payload;
        }

        $localeKeys = ['vi', 'zh_cn', 'en', 'zh_hk', 'ja', 'ko', 'de', 'fr', 'es', 'id', 'it', 'ru'];
        $keys = array_keys($payload);
        $intersection = array_intersect($keys, $localeKeys);

        if ($intersection) {
            if (! array_key_exists('vi', $payload)) {
                foreach ($sourcePriority as $sourceLocale) {
                    if (array_key_exists($sourceLocale, $payload)) {
                        $payload['vi'] = $payload[$sourceLocale];
                        break;
                    }
                }
            }

            return $payload;
        }

        foreach ($payload as $key => $value) {
            $payload[$key] = $this->addVietnameseLocaleToPayload($value, $sourcePriority);
        }

        return $payload;
    }
}
