<?php

namespace App\Services\ChordPro;

class ChordCatalog
{
    private const SHARPS = ['C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B'];

    private const FLATS = ['C', 'Db', 'D', 'Eb', 'E', 'F', 'Gb', 'G', 'Ab', 'A', 'Bb', 'B'];

    private const ROOTS = [
        'C',
        'C#',
        'Db',
        'D',
        'D#',
        'Eb',
        'E',
        'F',
        'F#',
        'Gb',
        'G',
        'G#',
        'Ab',
        'A',
        'A#',
        'Bb',
        'B',
    ];

    private const QUALITIES = [
        '',
        'm',
        '5',
        'aug',
        'dim',
        'sus2',
        'sus4',
        '6',
        'm6',
        '7',
        'maj7',
        'm7',
        'dim7',
        'm7b5',
        'aug7',
        'maj9',
        '9',
        'm9',
        'add9',
        '11',
        '13',
        '7sus4',
        '7b5',
        '7#5',
        '7b9',
        '7#9',
    ];

    private const FLAT_KEYS = ['F', 'Bb', 'Eb', 'Ab', 'Db', 'Gb', 'Cb', 'Dm', 'Gm', 'Cm', 'Fm', 'Bbm', 'Ebm'];

    private const PREFERRED_MAJOR = [
        0 => 'C',
        1 => 'Db',
        2 => 'D',
        3 => 'Eb',
        4 => 'E',
        5 => 'F',
        6 => 'F#',
        7 => 'G',
        8 => 'Ab',
        9 => 'A',
        10 => 'Bb',
        11 => 'B',
    ];

    private const PREFERRED_MINOR = [
        0 => 'Cm',
        1 => 'C#m',
        2 => 'Dm',
        3 => 'Ebm',
        4 => 'Em',
        5 => 'Fm',
        6 => 'F#m',
        7 => 'Gm',
        8 => 'G#m',
        9 => 'Am',
        10 => 'Bbm',
        11 => 'Bm',
    ];

    private const ENHARMONIC_TO_SHARP = [
        'Db' => 'C#',
        'Eb' => 'D#',
        'Gb' => 'F#',
        'Ab' => 'G#',
        'Bb' => 'A#',
        'Cb' => 'B',
        'E#' => 'F',
        'Fb' => 'E',
        'B#' => 'C',
    ];

    /** @return list<string> */
    public static function roots(): array
    {
        return self::ROOTS;
    }

    /** @return list<string> */
    public static function keys(): array
    {
        $keys = [];
        foreach (self::ROOTS as $root) {
            $keys[] = $root;
            $keys[] = $root . 'm';
        }

        return $keys;
    }

    /** @return list<string> */
    public static function qualities(): array
    {
        return self::QUALITIES;
    }

    /** @return list<string> */
    public static function chordSymbols(): array
    {
        $chords = [];
        foreach (self::ROOTS as $root) {
            foreach (self::QUALITIES as $quality) {
                $chords[] = $root . $quality;
            }
        }

        return $chords;
    }

    /** @return array{root: string, quality: string, bass: string|null}|null */
    public static function parseChord(string $chord): ?array
    {
        if (! preg_match('/^([A-G])([#b]?)([^\/]*)(?:\/([A-G][#b]?))?$/', trim($chord), $matches)) {
            return null;
        }

        return [
            'root' => $matches[1] . $matches[2],
            'quality' => $matches[3],
            'bass' => $matches[4] ?? null,
        ];
    }

    public static function transposeChord(string $chord, int $semitones, string|bool|null $targetKeyOrUseFlats = null): string
    {
        $parts = self::parseChord($chord);
        if ($parts === null) {
            return $chord;
        }

        $newRoot = self::shiftNote($parts['root'], $semitones, $targetKeyOrUseFlats);
        $newBass = $parts['bass'] !== null
            ? '/' . self::shiftNote($parts['bass'], $semitones, $targetKeyOrUseFlats)
            : '';

        return $newRoot . $parts['quality'] . $newBass;
    }

    public static function transposeLine(string $content, int $semitones, string|bool|null $targetKeyOrUseFlats = null): string
    {
        return preg_replace_callback(
            '/\[([^\]]+)\]/',
            fn(array $match) => '[' . self::transposeChord($match[1], $semitones, $targetKeyOrUseFlats) . ']',
            $content,
        ) ?? $content;
    }

    public static function transposeKey(string $key, int $semitones): string
    {
        if (! preg_match('/^([A-G][#b]?)(m)?$/', trim($key), $matches)) {
            return $key;
        }

        $index = self::noteIndex($matches[1]);
        if ($index === null) {
            return $key;
        }

        $newIndex = (($index + $semitones) % 12 + 12) % 12;

        return ($matches[2] ?? '') === 'm'
            ? self::PREFERRED_MINOR[$newIndex]
            : self::PREFERRED_MAJOR[$newIndex];
    }

    public static function usesFlats(string $key): bool
    {
        return str_contains($key, 'b');
    }

    /** @return array{newKey: string, useFlats: bool} */
    public static function transposeSong(string $originalKey, int $semitones): array
    {
        $newKey = self::transposeKey($originalKey, $semitones);

        return ['newKey' => $newKey, 'useFlats' => self::usesFlats($newKey)];
    }

    public static function semitonesBetweenKeys(string $sourceKey, string $targetKey): int
    {
        $source = self::parseKeyRoot($sourceKey);
        $target = self::parseKeyRoot($targetKey);
        if ($source === null || $target === null) {
            return 0;
        }

        $difference = ($target - $source + 12) % 12;

        return $difference > 6 ? $difference - 12 : $difference;
    }

    public static function inferKeyFromContent(string $content): ?string
    {
        if (
            preg_match('/\[([A-G][#b]?[^\]\/]*)\]/', $content, $matches)
            && preg_match('/^([A-G][#b]?)(m)?/', $matches[1], $keyMatch)
        ) {
            return $keyMatch[1] . ($keyMatch[2] ?? '');
        }

        return null;
    }

    public static function normalizeRootToSharp(string $note): ?string
    {
        if (isset(self::ENHARMONIC_TO_SHARP[$note])) {
            return self::ENHARMONIC_TO_SHARP[$note];
        }

        return in_array($note, self::SHARPS, true) ? $note : null;
    }

    private static function shiftNote(string $note, int $semitones, string|bool|null $targetKeyOrUseFlats): string
    {
        $index = self::noteIndex($note);
        if ($index === null) {
            return $note;
        }

        $newIndex = (($index + $semitones) % 12 + 12) % 12;
        $useFlats = is_bool($targetKeyOrUseFlats)
            ? $targetKeyOrUseFlats
            : ($targetKeyOrUseFlats !== null && (
                in_array($targetKeyOrUseFlats, self::FLAT_KEYS, true)
                || self::usesFlats($targetKeyOrUseFlats)
            ));

        return $useFlats ? self::FLATS[$newIndex] : self::SHARPS[$newIndex];
    }

    private static function noteIndex(string $note): ?int
    {
        $sharp = self::normalizeRootToSharp($note);
        if ($sharp === null) {
            return null;
        }

        $index = array_search($sharp, self::SHARPS, true);

        return $index === false ? null : $index;
    }

    private static function parseKeyRoot(string $key): ?int
    {
        if (! preg_match('/^([A-G][#b]?)(?:m)?$/', trim($key), $matches)) {
            return null;
        }

        return self::noteIndex($matches[1]);
    }
}
