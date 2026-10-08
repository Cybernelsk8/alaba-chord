<?php

namespace App\Services\ChordPro;

class ChordTransposer
{
    public static function transposeChord(string $chord, int $semitones, string|bool|null $targetKeyOrUseFlats = null): string
    {
        return ChordCatalog::transposeChord($chord, $semitones, $targetKeyOrUseFlats);
    }

    /**
     * Transpone una línea completa con formato ChordPro.
     */
    public static function transposeLine(string $content, int $semitones, string|bool|null $targetKeyOrUseFlats = null): string
    {
        return ChordCatalog::transposeLine($content, $semitones, $targetKeyOrUseFlats);
    }

    /**
     * Calcula la nueva tonalidad completa respetando alteraciones armónicas.
     */
    public static function transposeKey(string $key, int $semitones): string
    {
        return ChordCatalog::transposeKey($key, $semitones);
    }

    /**
     * Determina si una tonalidad dada utiliza bemoles.
     */
    public static function usesFlats(string $key): bool
    {
        return ChordCatalog::usesFlats($key);
    }

    /**
     * Helper integral de transposición.
     */
    /** @return array{newKey: string, useFlats: bool} */
    public static function transposeSong(string $originalKey, int $semitones): array
    {
        return ChordCatalog::transposeSong($originalKey, $semitones);
    }

    /**
     * Infiere la tonalidad basándose en el primer acorde de la canción.
     */
    public static function inferKeyFromContent(string $fullChordProContent): ?string
    {
        return ChordCatalog::inferKeyFromContent($fullChordProContent);
    }

    public static function semitonesBetweenKeys(string $sourceKey, string $targetKey): int
    {
        return ChordCatalog::semitonesBetweenKeys($sourceKey, $targetKey);
    }
}
