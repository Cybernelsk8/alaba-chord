<?php

namespace App\Services\ChordPro;

use App\Models\Song;
use Illuminate\Support\Facades\DB;

/**
 * @phpstan-type ParsedLine array{type: string, content: string}
 * @phpstan-type ParsedSection array{type: string, label: string, lines: list<ParsedLine>}
 * @phpstan-type PersistedSection array{type: string, label: string, lines: list<ParsedLine>, repeats_section_id?: int|null}
 */
class ChordProImporter
{
    public static function import(string $content, int $userId): Song
    {
        $parsed = self::parse($content, ['user_id' => $userId]);

        return DB::transaction(function () use ($parsed) {
            $song = Song::create($parsed['song']);
            self::persistSections($song, $parsed['sections']);

            return $song;
        });
    }

    /** @param array<string, mixed> $defaults
     * @return array{song: array<string, mixed>, sections: list<ParsedSection>}
     */
    public static function parse(string $content, array $defaults = []): array
    {
        $allowedSongFields = [
            'user_id',
            'title',
            'subtitle',
            'artist',
            'original_key',
            'capo',
            'time_signature',
            'tempo',
            'duration',
            'meta',
        ];
        $songData = array_replace(
            ['title' => 'Sin título', 'meta' => []],
            array_intersect_key($defaults, array_flip($allowedSongFields)),
        );
        $songData['meta'] = is_array($songData['meta'] ?? null) ? $songData['meta'] : [];

        $sections = [];
        $currentSection = null;
        $normalizedContent = str_replace(["\r\n", "\r"], "\n", $content);

        foreach (explode("\n", $normalizedContent) as $line) {
            $directiveLine = trim($line);

            if (preg_match('/^\{([^:]+)(?::\s*(.*))?\}$/', $directiveLine, $matches)) {
                $directive = strtolower(trim($matches[1]));
                $value = isset($matches[2]) ? trim($matches[2]) : null;
                $metadata = DirectiveMap::metadata($directive);
                $sectionStart = DirectiveMap::sectionStart($directive);

                if ($metadata !== null) {
                    $field = $metadata['field'];
                    $songData[$field] = in_array($field, ['capo', 'tempo'], true)
                        ? ($value === null || $value === '' ? null : (int) $value)
                        : $value;
                } elseif ($sectionStart !== null) {
                    $sections = self::flushSection($currentSection, $sections);
                    $currentSection = null;
                    $currentSection = [
                        'type' => $sectionStart['type'],
                        'label' => $value !== null && $value !== '' ? $value : $sectionStart['label'],
                        'lines' => [],
                    ];
                } elseif (DirectiveMap::sectionEnd($directive) !== null) {
                    $sections = self::flushSection($currentSection, $sections);
                    $currentSection = null;
                } elseif (in_array($directive, DirectiveMap::commentAliases(), true)) {
                    $currentSection ??= self::defaultVerseSection();
                    $currentSection['lines'][] = [
                        'type' => 'comment',
                        'content' => $value ?? '',
                    ];
                } else {
                    $songData['meta'][$directive] = $value;
                }

                continue;
            }

            if ($currentSection === null && $line === '') {
                continue;
            }

            $currentSection ??= self::defaultVerseSection();
            $currentSection['lines'][] = [
                'type' => $currentSection['type'] === 'tab' ? 'tab_line' : 'chord_lyrics',
                'content' => $line,
            ];
        }

        $sections = self::flushSection($currentSection, $sections);

        if (empty($songData['original_key'])) {
            $songData['original_key'] = ChordTransposer::inferKeyFromContent($content);
        }

        return ['song' => $songData, 'sections' => $sections];
    }

    /** @param list<PersistedSection> $sections */
    public static function persistSections(Song $song, array $sections): void
    {
        foreach ($sections as $sectionIndex => $sectionData) {
            $section = $song->sections()->create([
                'type' => $sectionData['type'],
                'label' => $sectionData['label'],
                'position' => $sectionIndex + 1,
                'repeats_section_id' => $sectionData['repeats_section_id'] ?? null,
            ]);

            foreach ($sectionData['lines'] as $lineIndex => $lineData) {
                $section->lines()->create([
                    'position' => $lineIndex + 1,
                    'type' => $lineData['type'],
                    'content' => $lineData['content'],
                ]);
            }
        }
    }

    /** @return ParsedSection */
    private static function defaultVerseSection(): array
    {
        return ['type' => 'verse', 'label' => 'Verso', 'lines' => []];
    }

    /** @param ParsedSection|null $currentSection
     * @param  list<ParsedSection>  $sections
     * @return list<ParsedSection>
     */
    private static function flushSection(?array $currentSection, array $sections): array
    {
        if ($currentSection !== null) {
            $sections[] = $currentSection;
        }

        return $sections;
    }
}
