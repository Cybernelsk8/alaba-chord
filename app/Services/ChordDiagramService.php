<?php

namespace App\Services;

use App\Services\ChordPro\ChordCatalog;

class ChordDiagramService
{
    /**
     * Diccionario de posiciones de acordes para guitarra.
     * Formato: [
     *   'frets' => [cuerda6, cuerda5, cuerda4, cuerda3, cuerda2, cuerda1],
     *   'fingers' => [dedoCuerda6, dedoCuerda5, dedoCuerda4, dedoCuerda3, dedoCuerda2, dedoCuerda1],
     *   'baseFret' => 1 (traste base donde inicia el dibujo),
     *   'barre' => null o ['fret' => traste, 'from' => cuerdaInicio, 'to' => cuerdaFin]
     * ]
     *
     * Dedos: 1 = Índice, 2 = Medio, 3 = Anular, 4 = Meñique, 0 = Sin dedo / Al aire
     */
    /** @var array<string, array{frets: list<int|string>, fingers?: list<int>, baseFret?: int, barre?: array{fret: int, from: int, to: int}}> */
    protected static array $chords = [
        // --- ACORDES NATURALES ---
        'C' => ['frets' => ['x', 3, 2, 0, 1, 0], 'fingers' => [0, 3, 2, 0, 1, 0]],
        'D' => ['frets' => ['x', 'x', 0, 2, 3, 2], 'fingers' => [0, 0, 0, 1, 3, 2]],
        'Dm' => ['frets' => ['x', 'x', 0, 2, 3, 1], 'fingers' => [0, 0, 0, 2, 3, 1]],
        'E' => ['frets' => [0, 2, 2, 1, 0, 0], 'fingers' => [0, 2, 3, 1, 0, 0]],
        'Em' => ['frets' => [0, 2, 2, 0, 0, 0], 'fingers' => [0, 1, 2, 0, 0, 0]],
        'F' => ['frets' => [1, 3, 3, 2, 1, 1], 'fingers' => [1, 3, 4, 2, 1, 1], 'barre' => ['fret' => 1, 'from' => 1, 'to' => 6]],
        'Fm' => ['frets' => [1, 3, 3, 1, 1, 1], 'fingers' => [1, 3, 4, 1, 1, 1], 'barre' => ['fret' => 1, 'from' => 1, 'to' => 6]],
        'G' => ['frets' => [3, 2, 0, 0, 0, 3], 'fingers' => [2, 1, 0, 0, 0, 3]],
        'Gm' => ['frets' => [3, 5, 5, 3, 3, 3], 'fingers' => [1, 3, 4, 1, 1, 1], 'baseFret' => 3, 'barre' => ['fret' => 3, 'from' => 1, 'to' => 6]],
        'A' => ['frets' => ['x', 0, 2, 2, 2, 0], 'fingers' => [0, 0, 1, 2, 3, 0]],
        'Am' => ['frets' => ['x', 0, 2, 2, 1, 0], 'fingers' => [0, 0, 2, 3, 1, 0]],
        'B' => ['frets' => ['x', 2, 4, 4, 4, 2], 'fingers' => [0, 1, 2, 3, 4, 1], 'baseFret' => 2, 'barre' => ['fret' => 2, 'from' => 1, 'to' => 5]],
        'Bm' => ['frets' => ['x', 2, 4, 4, 3, 2], 'fingers' => [0, 1, 3, 4, 2, 1], 'baseFret' => 2, 'barre' => ['fret' => 2, 'from' => 1, 'to' => 5]],

        // --- ACORDES SOSTENIDOS BÁSICOS (#) Y EQUIVALENCIAS ---
        // Do# / Reb
        'C#' => ['frets' => ['x', 4, 6, 6, 6, 4], 'fingers' => [0, 1, 2, 3, 4, 1], 'baseFret' => 4, 'barre' => ['fret' => 4, 'from' => 1, 'to' => 5]],
        'C#m' => ['frets' => ['x', 4, 6, 6, 5, 4], 'fingers' => [0, 1, 3, 4, 2, 1], 'baseFret' => 4, 'barre' => ['fret' => 4, 'from' => 1, 'to' => 5]],
        'Db' => ['frets' => ['x', 4, 6, 6, 6, 4], 'fingers' => [0, 1, 2, 3, 4, 1], 'baseFret' => 4, 'barre' => ['fret' => 4, 'from' => 1, 'to' => 5]],
        'Dbm' => ['frets' => ['x', 4, 6, 6, 5, 4], 'fingers' => [0, 1, 3, 4, 2, 1], 'baseFret' => 4, 'barre' => ['fret' => 4, 'from' => 1, 'to' => 5]],

        // Re# / Mib
        'D#' => ['frets' => ['x', 6, 8, 8, 8, 6], 'fingers' => [0, 1, 2, 3, 4, 1], 'baseFret' => 6, 'barre' => ['fret' => 6, 'from' => 1, 'to' => 5]],
        'D#m' => ['frets' => ['x', 6, 8, 8, 7, 6], 'fingers' => [0, 1, 3, 4, 2, 1], 'baseFret' => 6, 'barre' => ['fret' => 6, 'from' => 1, 'to' => 5]],
        'Eb' => ['frets' => ['x', 6, 8, 8, 8, 6], 'fingers' => [0, 1, 2, 3, 4, 1], 'baseFret' => 6, 'barre' => ['fret' => 6, 'from' => 1, 'to' => 5]],
        'Ebm' => ['frets' => ['x', 6, 8, 8, 7, 6], 'fingers' => [0, 1, 3, 4, 2, 1], 'baseFret' => 6, 'barre' => ['fret' => 6, 'from' => 1, 'to' => 5]],

        // Fa# / Solb
        'F#' => ['frets' => [2, 4, 4, 3, 2, 2], 'fingers' => [1, 3, 4, 2, 1, 1], 'baseFret' => 2, 'barre' => ['fret' => 2, 'from' => 1, 'to' => 6]],
        'F#m' => ['frets' => [2, 4, 4, 2, 2, 2], 'fingers' => [1, 3, 4, 1, 1, 1], 'baseFret' => 2, 'barre' => ['fret' => 2, 'from' => 1, 'to' => 6]],
        'Gb' => ['frets' => [2, 4, 4, 3, 2, 2], 'fingers' => [1, 3, 4, 2, 1, 1], 'baseFret' => 2, 'barre' => ['fret' => 2, 'from' => 1, 'to' => 6]],
        'Gbm' => ['frets' => [2, 4, 4, 2, 2, 2], 'fingers' => [1, 3, 4, 1, 1, 1], 'baseFret' => 2, 'barre' => ['fret' => 2, 'from' => 1, 'to' => 6]],

        // Sol# / Lab
        'G#' => ['frets' => [4, 6, 6, 5, 4, 4], 'fingers' => [1, 3, 4, 2, 1, 1], 'baseFret' => 4, 'barre' => ['fret' => 4, 'from' => 1, 'to' => 6]],
        'G#m' => ['frets' => [4, 6, 6, 4, 4, 4], 'fingers' => [1, 3, 4, 1, 1, 1], 'baseFret' => 4, 'barre' => ['fret' => 4, 'from' => 1, 'to' => 6]],
        'Ab' => ['frets' => [4, 6, 6, 5, 4, 4], 'fingers' => [1, 3, 4, 2, 1, 1], 'baseFret' => 4, 'barre' => ['fret' => 4, 'from' => 1, 'to' => 6]],
        'Abm' => ['frets' => [4, 6, 6, 4, 4, 4], 'fingers' => [1, 3, 4, 1, 1, 1], 'baseFret' => 4, 'barre' => ['fret' => 4, 'from' => 1, 'to' => 6]],

        // La# / Sib
        'A#' => ['frets' => ['x', 1, 3, 3, 3, 1], 'fingers' => [0, 1, 2, 3, 4, 1], 'baseFret' => 1, 'barre' => ['fret' => 1, 'from' => 1, 'to' => 5]],
        'A#m' => ['frets' => ['x', 1, 3, 3, 2, 1], 'fingers' => [0, 1, 3, 4, 2, 1], 'baseFret' => 1, 'barre' => ['fret' => 1, 'from' => 1, 'to' => 5]],
        'Bb' => ['frets' => ['x', 1, 3, 3, 3, 1], 'fingers' => [0, 1, 2, 3, 4, 1], 'baseFret' => 1, 'barre' => ['fret' => 1, 'from' => 1, 'to' => 5]],
        'Bbm' => ['frets' => ['x', 1, 3, 3, 2, 1], 'fingers' => [0, 1, 3, 4, 2, 1], 'baseFret' => 1, 'barre' => ['fret' => 1, 'from' => 1, 'to' => 5]],

        // --- ACORDES SÉPTIMA NATURALES (7, m7, maj7) ---
        'C7' => ['frets' => ['x', 3, 2, 3, 1, 0], 'fingers' => [0, 3, 2, 4, 1, 0]],
        'Cmaj7' => ['frets' => ['x', 3, 2, 0, 0, 0], 'fingers' => [0, 3, 2, 0, 0, 0]],
        'Cm7' => ['frets' => ['x', 3, 5, 3, 4, 3], 'fingers' => [0, 1, 3, 1, 2, 1], 'baseFret' => 3, 'barre' => ['fret' => 3, 'from' => 1, 'to' => 5]],

        'D7' => ['frets' => ['x', 'x', 0, 2, 1, 2], 'fingers' => [0, 0, 0, 2, 1, 3]],
        'Dm7' => ['frets' => ['x', 'x', 0, 2, 1, 1], 'fingers' => [0, 0, 0, 2, 1, 1], 'barre' => ['fret' => 1, 'from' => 1, 'to' => 2]],
        'Dmaj7' => ['frets' => ['x', 'x', 0, 2, 2, 2], 'fingers' => [0, 0, 0, 1, 2, 3]],

        'E7' => ['frets' => [0, 2, 0, 1, 0, 0], 'fingers' => [0, 2, 0, 1, 0, 0]],
        'Em7' => ['frets' => [0, 2, 2, 0, 3, 0], 'fingers' => [0, 1, 2, 0, 3, 0]],
        'Emaj7' => ['frets' => [0, 2, 1, 1, 0, 0], 'fingers' => [0, 2, 1, 1, 0, 0]],

        'F7' => ['frets' => [1, 3, 1, 2, 1, 1], 'fingers' => [1, 3, 1, 2, 1, 1], 'barre' => ['fret' => 1, 'from' => 1, 'to' => 6]],
        'Fm7' => ['frets' => [1, 3, 1, 1, 1, 1], 'fingers' => [1, 3, 1, 1, 1, 1], 'barre' => ['fret' => 1, 'from' => 1, 'to' => 6]],
        'Fmaj7' => ['frets' => ['x', 'x', 3, 2, 1, 0], 'fingers' => [0, 0, 3, 2, 1, 0]],

        'G7' => ['frets' => [3, 2, 0, 0, 0, 1], 'fingers' => [3, 2, 0, 0, 0, 1]],
        'Gm7' => ['frets' => [3, 5, 3, 3, 3, 3], 'fingers' => [1, 3, 1, 1, 1, 1], 'baseFret' => 3, 'barre' => ['fret' => 3, 'from' => 1, 'to' => 6]],
        'Gmaj7' => ['frets' => [3, 2, 0, 0, 0, 2], 'fingers' => [2, 1, 0, 0, 0, 3]],

        'A7' => ['frets' => ['x', 0, 2, 0, 2, 0], 'fingers' => [0, 0, 1, 0, 2, 0]],
        'Am7' => ['frets' => ['x', 0, 2, 0, 1, 0], 'fingers' => [0, 0, 2, 0, 1, 0]],
        'Amaj7' => ['frets' => ['x', 0, 2, 1, 2, 0], 'fingers' => [0, 0, 2, 1, 3, 0]],

        'B7' => ['frets' => ['x', 2, 1, 2, 0, 2], 'fingers' => [0, 2, 1, 3, 0, 4]],
        'Bm7' => ['frets' => ['x', 2, 4, 2, 3, 2], 'fingers' => [0, 1, 3, 1, 2, 1], 'baseFret' => 2, 'barre' => ['fret' => 2, 'from' => 1, 'to' => 5]],
        'Bmaj7' => ['frets' => ['x', 2, 4, 3, 4, 2], 'fingers' => [0, 1, 3, 2, 4, 1], 'baseFret' => 2, 'barre' => ['fret' => 2, 'from' => 1, 'to' => 5]],

        // --- SOSTENIDOS SÉPTIMA (#) ---
        'C#7' => ['frets' => ['x', 4, 3, 4, 2, 'x'], 'fingers' => [0, 3, 2, 4, 1, 0]],
        'C#m7' => ['frets' => ['x', 4, 6, 4, 5, 4], 'fingers' => [0, 1, 3, 1, 2, 1], 'baseFret' => 4, 'barre' => ['fret' => 4, 'from' => 1, 'to' => 5]],
        'C#maj7' => ['frets' => ['x', 4, 6, 5, 6, 4], 'fingers' => [0, 1, 3, 2, 4, 1], 'baseFret' => 4, 'barre' => ['fret' => 4, 'from' => 1, 'to' => 5]],

        'D#7' => ['frets' => ['x', 6, 5, 6, 4, 'x'], 'fingers' => [0, 3, 2, 4, 1, 0]],
        'D#m7' => ['frets' => ['x', 6, 8, 6, 7, 6], 'fingers' => [0, 1, 3, 1, 2, 1], 'baseFret' => 6, 'barre' => ['fret' => 6, 'from' => 1, 'to' => 5]],
        'D#maj7' => ['frets' => ['x', 6, 8, 7, 8, 6], 'fingers' => [0, 1, 3, 2, 4, 1], 'baseFret' => 6, 'barre' => ['fret' => 6, 'from' => 1, 'to' => 5]],

        'F#7' => ['frets' => [2, 4, 2, 3, 2, 2], 'fingers' => [1, 3, 1, 2, 1, 1], 'baseFret' => 2, 'barre' => ['fret' => 2, 'from' => 1, 'to' => 6]],
        'F#m7' => ['frets' => [2, 4, 2, 2, 2, 2], 'fingers' => [1, 3, 1, 1, 1, 1], 'baseFret' => 2, 'barre' => ['fret' => 2, 'from' => 1, 'to' => 6]],
        'F#maj7' => ['frets' => [2, 4, 3, 3, 2, 2], 'fingers' => [1, 4, 2, 3, 1, 1], 'baseFret' => 2, 'barre' => ['fret' => 2, 'from' => 1, 'to' => 6]],

        'G#7' => ['frets' => [4, 6, 4, 5, 4, 4], 'fingers' => [1, 3, 1, 2, 1, 1], 'baseFret' => 4, 'barre' => ['fret' => 4, 'from' => 1, 'to' => 6]],
        'G#m7' => ['frets' => [4, 6, 4, 4, 4, 4], 'fingers' => [1, 3, 1, 1, 1, 1], 'baseFret' => 4, 'barre' => ['fret' => 4, 'from' => 1, 'to' => 6]],
        'G#maj7' => ['frets' => [4, 6, 5, 5, 4, 4], 'fingers' => [1, 4, 2, 3, 1, 1], 'baseFret' => 4, 'barre' => ['fret' => 4, 'from' => 1, 'to' => 6]],

        'A#7' => ['frets' => ['x', 1, 3, 1, 3, 1], 'fingers' => [0, 1, 3, 1, 4, 1], 'baseFret' => 1, 'barre' => ['fret' => 1, 'from' => 1, 'to' => 5]],
        'A#m7' => ['frets' => ['x', 1, 3, 1, 2, 1], 'fingers' => [0, 1, 3, 1, 2, 1], 'baseFret' => 1, 'barre' => ['fret' => 1, 'from' => 1, 'to' => 5]],
        'A#maj7' => ['frets' => ['x', 1, 3, 2, 3, 1], 'fingers' => [0, 1, 3, 2, 4, 1], 'baseFret' => 1, 'barre' => ['fret' => 1, 'from' => 1, 'to' => 5]],

        // --- BEMOLES SÉPTIMA (b) ---
        'Db7' => ['frets' => ['x', 4, 3, 4, 2, 'x'], 'fingers' => [0, 3, 2, 4, 1, 0]],
        'Dbm7' => ['frets' => ['x', 4, 6, 4, 5, 4], 'fingers' => [0, 1, 3, 1, 2, 1], 'baseFret' => 4, 'barre' => ['fret' => 4, 'from' => 1, 'to' => 5]],
        'Eb7' => ['frets' => ['x', 6, 5, 6, 4, 'x'], 'fingers' => [0, 3, 2, 4, 1, 0]],
        'Ebm7' => ['frets' => ['x', 6, 8, 6, 7, 6], 'fingers' => [0, 1, 3, 1, 2, 1], 'baseFret' => 6, 'barre' => ['fret' => 6, 'from' => 1, 'to' => 5]],
        'Ab7' => ['frets' => [4, 6, 4, 5, 4, 4], 'fingers' => [1, 3, 1, 2, 1, 1], 'baseFret' => 4, 'barre' => ['fret' => 4, 'from' => 1, 'to' => 6]],
        'Abm7' => ['frets' => [4, 6, 4, 4, 4, 4], 'fingers' => [1, 3, 1, 1, 1, 1], 'baseFret' => 4, 'barre' => ['fret' => 4, 'from' => 1, 'to' => 6]],
        'Bb7' => ['frets' => ['x', 1, 3, 1, 3, 1], 'fingers' => [0, 1, 3, 1, 4, 1], 'baseFret' => 1, 'barre' => ['fret' => 1, 'from' => 1, 'to' => 5]],
        'Bbm7' => ['frets' => ['x', 1, 3, 1, 2, 1], 'fingers' => [0, 1, 3, 1, 2, 1], 'baseFret' => 1, 'barre' => ['fret' => 1, 'from' => 1, 'to' => 5]],
    ];

    public static function getSvg(string $chordName): string
    {
        $cleanChord = preg_replace('/\/.*$/', '', trim($chordName));
        $chordData = self::findVoicing($cleanChord);
        if ($chordData === null) {
            return '';
        }

        $frets = $chordData['frets'];
        $fingers = $chordData['fingers'] ?? array_fill(0, 6, 0);
        $baseFret = $chordData['baseFret'] ?? 1;
        $barre = $chordData['barre'] ?? null;

        $svg = '<svg viewBox="0 0 110 130" width="130" height="150" xmlns="http://www.w3.org/2000/svg" class="select-none">';

        // Traste Base o Cejuela
        if ($baseFret > 1) {
            $svg .= "<text x='2' y='36' font-size='10' font-weight='bold' fill='#6b7280'>{$baseFret}fr</text>";
        } else {
            $svg .= '<rect x="20" y="20" width="70" height="4" fill="#6b7280" />';
        }

        // 4 Trastes (líneas horizontales)
        for ($i = 1; $i <= 4; $i++) {
            $y = 20 + ($i * 20);
            $svg .= "<line x1='20' y1='{$y}' x2='90' y2='{$y}' stroke='#9ca3af' stroke-width='1.5' />";
        }

        // 6 Cuerdas (líneas verticales)
        for ($i = 0; $i < 6; $i++) {
            $x = 20 + ($i * 14);
            $svg .= "<line x1='{$x}' y1='20' x2='{$x}' y2='100' stroke='#9ca3af' stroke-width='1.5' />";
        }

        // Dibujar Cejilla si aplica
        if ($barre) {
            $fretOffset = ($barre['fret'] - $baseFret) + 1;
            $y = 20 + ($fretOffset * 20) - 10;
            $x1 = 20 + ((6 - $barre['to']) * 14);
            $x2 = 20 + ((6 - $barre['from']) * 14);

            $svg .= "<line x1='{$x1}' y1='{$y}' x2='{$x2}' y2='{$y}' stroke='#2563eb' stroke-width='10' stroke-linecap='round' />";
        }

        // Dibujar marcas de pisada con el número del dedo
        foreach ($frets as $stringIndex => $fret) {
            $x = 20 + ($stringIndex * 14);
            $finger = $fingers[$stringIndex] ?? 0;

            if ($fret === 'x') {
                $svg .= "<text x='{$x}' y='14' font-size='12' text-anchor='middle' fill='#ef4444' font-weight='bold'>×</text>";
            } elseif ($fret === 0) {
                $svg .= "<circle cx='{$x}' cy='10' r='3.5' stroke='#10b981' stroke-width='1.5' fill='none' />";
            } elseif (is_int($fret)) {
                $relativeFret = ($fret - $baseFret) + 1;
                $y = 20 + ($relativeFret * 20) - 10;

                // Dibujar si no queda oculto bajo la cejilla
                if (! $barre || $fret !== $barre['fret'] || ($finger > 1 && $barre['fret'] === $fret)) {
                    $svg .= "<circle cx='{$x}' cy='{$y}' r='6' fill='#2563eb' />";

                    if ($finger > 0) {
                        $svg .= "<text x='{$x}' y='" . ($y + 3) . "' font-size='8' font-weight='bold' fill='#ffffff' text-anchor='middle'>{$finger}</text>";
                    }
                }
            }
        }

        $svg .= '</svg>';

        return $svg;
    }

    public static function hasDiagram(string $chordName): bool
    {
        $cleanChord = preg_replace('/\/.*$/', '', trim($chordName));

        return self::findVoicing($cleanChord) !== null;
    }

    /** @return list<string> */
    public static function availableChords(): array
    {
        return array_values(array_filter(
            ChordCatalog::chordSymbols(),
            fn(string $chord) => self::hasDiagram($chord),
        ));
    }

    /** @return array{frets: list<int|string>, fingers?: list<int>, baseFret?: int, barre?: array{fret: int, from: int, to: int}}|null */
    private static function findVoicing(string $chordName): ?array
    {
        if (isset(self::$chords[$chordName])) {
            return self::$chords[$chordName];
        }

        $parts = ChordCatalog::parseChord($chordName);
        if ($parts === null) {
            return null;
        }

        $root = ChordCatalog::normalizeRootToSharp($parts['root']);
        if ($root === null) {
            return null;
        }

        return self::$chords[$root . $parts['quality']] ?? null;
    }
}
