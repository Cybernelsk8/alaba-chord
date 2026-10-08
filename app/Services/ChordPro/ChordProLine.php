<?php

namespace App\Services\ChordPro;

class ChordProLine
{
    // Extrae ["G" => 8, "C" => 15, ...] -> acorde y posición en texto SIN corchetes
    /** @return list<array{chord: string, position: int}> */
    public static function extractChords(string $content): array
    {
        preg_match_all('/\[([^\]]+)\]/', $content, $matches, PREG_OFFSET_CAPTURE);
        $chords = [];

        foreach ($matches[1] as [$chord, $offset]) {
            $textBeforeChord = self::stripChords(substr($content, 0, $offset - 1));
            $chords[] = [
                'chord' => $chord,
                'position' => mb_strlen($textBeforeChord),
            ];
        }

        return $chords;
    }

    public static function stripChords(string $content): string
    {
        return preg_replace('/\[([^\]]+)\]/', '', $content);
    }

    public static function transpose(string $content, int $semitones): string
    {
        return ChordTransposer::transposeLine($content, $semitones);
    }
}
