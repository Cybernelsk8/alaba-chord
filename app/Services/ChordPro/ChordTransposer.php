<?php

namespace App\Services\ChordPro;

class ChordTransposer
{
    // Escala cromática en sostenidos y en bemoles
    private const SHARPS = ['C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B'];

    private const FLATS = ['C', 'Db', 'D', 'Eb', 'E', 'F', 'Gb', 'G', 'Ab', 'A', 'Bb', 'B'];

    // Tonos que tradicionalmente se escriben con bemoles
    private const FLAT_KEYS = ['F', 'Bb', 'Eb', 'Ab', 'Db', 'Gb', 'Cb', 'Dm', 'Gm', 'Cm', 'Fm', 'Bbm', 'Ebm'];

    private const PREFERRED_MAJOR = [
        0 => 'C', 1 => 'Db', 2 => 'D', 3 => 'Eb', 4 => 'E', 5 => 'F',
        6 => 'F#', 7 => 'G', 8 => 'Ab', 9 => 'A', 10 => 'Bb', 11 => 'B',
    ];

    private const PREFERRED_MINOR = [
        0 => 'Cm', 1 => 'C#m', 2 => 'Dm', 3 => 'Ebm', 4 => 'Em', 5 => 'Fm',
        6 => 'F#m', 7 => 'Gm', 8 => 'G#m', 9 => 'Am', 10 => 'Bbm', 11 => 'Bm',
    ];

    /**
     * Transpone un acorde individual.
     * $targetKeyOrUseFlats puede ser un tono string ("F", "Bb") o un booleano (true/false).
     */
    public static function transposeChord(string $chord, int $semitones, string|bool|null $targetKeyOrUseFlats = null): string
    {
        if (! preg_match('/^([A-G])([#b]?)([^\/]*)(?:\/([A-G][#b]?))?$/', trim($chord), $m)) {
            return $chord; // No parseable (ej: "N.C.", "%")
        }

        [, $root, $accidental, $quality, $bass] = array_pad($m, 5, null);

        $newRoot = self::shiftNote($root.$accidental, $semitones, $targetKeyOrUseFlats);
        $newBass = $bass ? self::shiftNote($bass, $semitones, $targetKeyOrUseFlats) : null;

        return $newRoot.$quality.($newBass ? "/{$newBass}" : '');
    }

    /**
     * Transpone una línea completa con formato ChordPro.
     */
    public static function transposeLine(string $content, int $semitones, string|bool|null $targetKeyOrUseFlats = null): string
    {
        return preg_replace_callback('/\[([^\]]+)\]/', function ($m) use ($semitones, $targetKeyOrUseFlats) {
            return '['.self::transposeChord($m[1], $semitones, $targetKeyOrUseFlats).']';
        }, $content);
    }

    /**
     * Desplaza una nota individual.
     */
    private static function shiftNote(string $note, int $semitones, string|bool|null $targetKeyOrUseFlats): string
    {
        $index = self::indexOf($note);

        if ($index === null) {
            return $note; // Si no la encuentra, la deja intacta
        }

        $newIndex = (($index + $semitones) % 12 + 12) % 12;

        $useFlats = is_bool($targetKeyOrUseFlats)
            ? $targetKeyOrUseFlats
            : ($targetKeyOrUseFlats && in_array($targetKeyOrUseFlats, self::FLAT_KEYS, true));

        return $useFlats ? self::FLATS[$newIndex] : self::SHARPS[$newIndex];
    }

    /**
     * Obtiene el índice (0-11) de una nota dentro de la escala cromática.
     */
    private static function indexOf(string $note): ?int
    {
        $pos = array_search($note, self::SHARPS, true);
        if ($pos === false) {
            $pos = array_search($note, self::FLATS, true);
        }

        return $pos !== false ? $pos : null;
    }

    /**
     * Calcula la nueva tonalidad completa respetando alteraciones armónicas.
     */
    public static function transposeKey(string $key, int $semitones): string
    {
        if (! preg_match('/^([A-G][#b]?)(m)?$/', trim($key), $m)) {
            return $key;
        }

        [, $root, $minorSuffix] = array_pad($m, 3, null);
        $isMinor = $minorSuffix === 'm';

        $index = self::indexOf($root);
        if ($index === null) {
            return $key;
        }

        $newIndex = (($index + $semitones) % 12 + 12) % 12;

        return $isMinor ? self::PREFERRED_MINOR[$newIndex] : self::PREFERRED_MAJOR[$newIndex];
    }

    /**
     * Determina si una tonalidad dada utiliza bemoles.
     */
    public static function usesFlats(string $key): bool
    {
        return str_contains($key, 'b');
    }

    /**
     * Helper integral de transposición.
     */
    public static function transposeSong(string $originalKey, int $semitones): array
    {
        $newKey = self::transposeKey($originalKey, $semitones);
        $useFlats = self::usesFlats($newKey);

        return compact('newKey', 'useFlats');
    }

    /**
     * Infiere la tonalidad basándose en el primer acorde de la canción.
     */
    public static function inferKeyFromContent(string $fullChordProContent): ?string
    {
        if (preg_match('/\[([A-G][#b]?[^\]\/]*)\]/', $fullChordProContent, $matches)) {
            if (preg_match('/^([A-G][#b]?)(m)?/', $matches[1], $keyMatch)) {
                return $keyMatch[1].($keyMatch[2] ?? '');
            }
        }

        return null;
    }
}
