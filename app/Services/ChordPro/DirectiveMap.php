<?php

namespace App\Services\ChordPro;

class DirectiveMap
{
    private const METADATA = [
        'title' => ['aliases' => ['t'], 'field' => 'title'],
        'subtitle' => ['aliases' => ['st'], 'field' => 'subtitle'],
        'artist' => ['aliases' => [], 'field' => 'artist'],
        'key' => ['aliases' => [], 'field' => 'original_key'],
        'capo' => ['aliases' => [], 'field' => 'capo'],
        'tempo' => ['aliases' => [], 'field' => 'tempo'],
        'time' => ['aliases' => [], 'field' => 'time_signature'],
        'duration' => ['aliases' => [], 'field' => 'duration'],
    ];

    private const SECTIONS = [
        'verse' => ['aliases' => ['sov'], 'label' => 'Verso'],
        'chorus' => ['aliases' => ['soc'], 'label' => 'Coro'],
        'bridge' => ['aliases' => ['sob'], 'label' => 'Puente'],
        'pre_chorus' => ['aliases' => ['sop'], 'label' => 'Pre-coro'],
        'tab' => ['aliases' => ['sot'], 'label' => 'Tablatura'],
        'grid' => ['aliases' => [], 'label' => 'Grid'],
        'intro' => ['aliases' => [], 'label' => 'Introducción'],
        'outro' => ['aliases' => [], 'label' => 'Final'],
        'instrumental' => ['aliases' => [], 'label' => 'Instrumental'],
        'other' => ['aliases' => [], 'label' => 'Otra sección'],
    ];

    private const SECTION_ENDS = [
        'verse' => ['directive' => 'end_of_verse', 'aliases' => ['eov']],
        'chorus' => ['directive' => 'end_of_chorus', 'aliases' => ['eoc']],
        'bridge' => ['directive' => 'end_of_bridge', 'aliases' => ['eob']],
        'pre_chorus' => ['directive' => 'end_of_pre_chorus', 'aliases' => ['eop']],
        'tab' => ['directive' => 'end_of_tab', 'aliases' => ['eot']],
    ];

    /** @return array{name: string, aliases: list<string>, field: string}|null */
    public static function metadata(string $directive): ?array
    {
        foreach (self::METADATA as $name => $metadata) {
            if ($directive === $name || in_array($directive, $metadata['aliases'], true)) {
                return ['name' => $name, ...$metadata];
            }
        }

        return null;
    }

    /** @return array{type: string, aliases: list<string>, label: string}|null */
    public static function sectionStart(string $directive): ?array
    {
        foreach (self::SECTIONS as $type => $section) {
            if ($directive === $type || $directive === 'start_of_' . $type || in_array($directive, $section['aliases'], true)) {
                return ['type' => $type, ...$section];
            }
        }

        return null;
    }

    public static function sectionEnd(string $directive): ?string
    {
        foreach (self::SECTION_ENDS as $type => $ending) {
            if ($directive === $ending['directive'] || in_array($directive, $ending['aliases'], true)) {
                return $type;
            }
        }

        return null;
    }

    /** @return list<string> */
    public static function commentAliases(): array
    {
        return ['comment', 'c'];
    }

    public static function startDirective(string $sectionType): string
    {
        return 'start_of_' . $sectionType;
    }

    public static function endDirective(string $sectionType): ?string
    {
        return self::SECTION_ENDS[$sectionType]['directive'] ?? null;
    }

    /** @return list<array{directive: string, aliases: list<string>, field: string}> */
    public static function metadataDefinitions(): array
    {
        return array_map(
            fn(string $name, array $metadata) => [
                'directive' => $name,
                'aliases' => $metadata['aliases'],
                'field' => $metadata['field'],
            ],
            array_keys(self::METADATA),
            self::METADATA,
        );
    }

    /** @return list<array{type: string, start: string, end: string|null, aliases: list<string>, label: string}> */
    public static function sectionDefinitions(): array
    {
        return array_map(
            fn(string $type, array $section) => [
                'type' => $type,
                'start' => self::startDirective($type),
                'end' => self::endDirective($type),
                'aliases' => $section['aliases'],
                'label' => $section['label'],
            ],
            array_keys(self::SECTIONS),
            self::SECTIONS,
        );
    }
}
