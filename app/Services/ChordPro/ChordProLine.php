<?php

namespace App\Services\ChordPro;

class ChordProLine
{
    // Extrae ["G" => 8, "C" => 15, ...] -> acorde y posición en texto SIN corchetes
    public static function extractChords(string $content): array
    {
        preg_match_all('/\[([^\]]+)\]/', $content, $matches, PREG_OFFSET_CAPTURE);
        // ...calcular offset restando corchetes previos
    }

    public static function stripChords(string $content): string
    {
        return preg_replace('/\[([^\]]+)\]/', '', $content);
    }

    public static function transpose(string $content, int $semitones): string
    {
        return preg_replace_callback('/\[([^\]]+)\]/', function ($m) use ($semitones) {
            return '['.ChordTransposer::transpose($m[1], $semitones).']';
        }, $content);
    }
}
