<?php

namespace App\Services;

class ChordDiagramService
{
    /**
     * Diccionario de posiciones de acordes para guitarra.
     * Formato: [
     *   'frets' => [cuerda6, cuerda5, cuerda4, cuerda3, cuerda2, cuerda1],
     *   'baseFret' => 1 (traste base donde inicia el dibujo),
     *   'barre' => null o ['fret' => traste, 'from' => cuerdaInicio, 'to' => cuerdaFin]
     * ]
     */
    protected static array $chords = [
        // Acordes Naturales
        'C' => ['frets' => ['x', 3, 2, 0, 1, 0]],
        'D' => ['frets' => ['x', 'x', 0, 2, 3, 2]],
        'Dm' => ['frets' => ['x', 'x', 0, 2, 3, 1]],
        'E' => ['frets' => [0, 2, 2, 1, 0, 0]],
        'Em' => ['frets' => [0, 2, 2, 0, 0, 0]],
        'F' => ['frets' => [1, 3, 3, 2, 1, 1], 'barre' => ['fret' => 1, 'from' => 1, 'to' => 6]],
        'Fm' => ['frets' => [1, 3, 3, 1, 1, 1], 'barre' => ['fret' => 1, 'from' => 1, 'to' => 6]],
        'G' => ['frets' => [3, 2, 0, 0, 0, 3]],
        'Gm' => ['frets' => [3, 5, 5, 3, 3, 3], 'baseFret' => 3, 'barre' => ['fret' => 3, 'from' => 1, 'to' => 6]],
        'A' => ['frets' => ['x', 0, 2, 2, 2, 0]],
        'Am' => ['frets' => ['x', 0, 2, 2, 1, 0]],
        'B' => ['frets' => ['x', 2, 4, 4, 4, 2], 'baseFret' => 2, 'barre' => ['fret' => 2, 'from' => 1, 'to' => 5]],
        'Bm' => ['frets' => ['x', 2, 4, 4, 3, 2], 'baseFret' => 2, 'barre' => ['fret' => 2, 'from' => 1, 'to' => 5]],

        // Sostenidos (#)
        'C#' => ['frets' => ['x', 4, 6, 6, 6, 4], 'baseFret' => 4, 'barre' => ['fret' => 4, 'from' => 1, 'to' => 5]],
        'C#m' => ['frets' => ['x', 4, 6, 6, 5, 4], 'baseFret' => 4, 'barre' => ['fret' => 4, 'from' => 1, 'to' => 5]],
        'D#' => ['frets' => ['x', 6, 8, 8, 8, 6], 'baseFret' => 6, 'barre' => ['fret' => 6, 'from' => 1, 'to' => 5]],
        'D#m' => ['frets' => ['x', 6, 8, 8, 7, 6], 'baseFret' => 6, 'barre' => ['fret' => 6, 'from' => 1, 'to' => 5]],
        'F#' => ['frets' => [2, 4, 4, 3, 2, 2], 'baseFret' => 2, 'barre' => ['fret' => 2, 'from' => 1, 'to' => 6]],
        'F#m' => ['frets' => [2, 4, 4, 2, 2, 2], 'baseFret' => 2, 'barre' => ['fret' => 2, 'from' => 1, 'to' => 6]],
        'G#' => ['frets' => [4, 6, 6, 5, 4, 4], 'baseFret' => 4, 'barre' => ['fret' => 4, 'from' => 1, 'to' => 6]],
        'G#m' => ['frets' => [4, 6, 6, 4, 4, 4], 'baseFret' => 4, 'barre' => ['fret' => 4, 'from' => 1, 'to' => 6]],
        'A#' => ['frets' => ['x', 1, 3, 3, 3, 1], 'baseFret' => 1, 'barre' => ['fret' => 1, 'from' => 1, 'to' => 5]],
        'A#m' => ['frets' => ['x', 1, 3, 3, 2, 1], 'baseFret' => 1, 'barre' => ['fret' => 1, 'from' => 1, 'to' => 5]],

        // Bemoles (Db, Eb, Gb, Ab, Bb)
        'Db' => ['frets' => ['x', 4, 6, 6, 6, 4], 'baseFret' => 4, 'barre' => ['fret' => 4, 'from' => 1, 'to' => 5]],
        'Dbm' => ['frets' => ['x', 4, 6, 6, 5, 4], 'baseFret' => 4, 'barre' => ['fret' => 4, 'from' => 1, 'to' => 5]],
        'Eb' => ['frets' => ['x', 6, 8, 8, 8, 6], 'baseFret' => 6, 'barre' => ['fret' => 6, 'from' => 1, 'to' => 5]],
        'Ebm' => ['frets' => ['x', 6, 8, 8, 7, 6], 'baseFret' => 6, 'barre' => ['fret' => 6, 'from' => 1, 'to' => 5]],
        'Gb' => ['frets' => [2, 4, 4, 3, 2, 2], 'baseFret' => 2, 'barre' => ['fret' => 2, 'from' => 1, 'to' => 6]],
        'Gbm' => ['frets' => [2, 4, 4, 2, 2, 2], 'baseFret' => 2, 'barre' => ['fret' => 2, 'from' => 1, 'to' => 6]],
        'Ab' => ['frets' => [4, 6, 6, 5, 4, 4], 'baseFret' => 4, 'barre' => ['fret' => 4, 'from' => 1, 'to' => 6]],
        'Abm' => ['frets' => [4, 6, 6, 4, 4, 4], 'baseFret' => 4, 'barre' => ['fret' => 4, 'from' => 1, 'to' => 6]],
        'Bb' => ['frets' => ['x', 1, 3, 3, 3, 1], 'baseFret' => 1, 'barre' => ['fret' => 1, 'from' => 1, 'to' => 5]],
        'Bbm' => ['frets' => ['x', 1, 3, 3, 2, 1], 'baseFret' => 1, 'barre' => ['fret' => 1, 'from' => 1, 'to' => 5]],
    ];

    public static function getSvg(string $chordName): string
    {
        // Limpiar el acorde (quitar bajo invertido ej. C/E -> C)
        $cleanChord = preg_replace('/\/.*$/', '', trim($chordName));

        // Obtener la configuración o usar una genérica si no existe
        $chordData = self::$chords[$cleanChord] ?? [
            'frets' => ['x', 'x', 'x', 'x', 'x', 'x'],
            'baseFret' => 1,
        ];

        $frets = $chordData['frets'];
        $baseFret = $chordData['baseFret'] ?? 1;
        $barre = $chordData['barre'] ?? null;

        $svg = '<svg viewBox="0 0 110 130" width="130" height="150" xmlns="http://www.w3.org/2000/svg" class="select-none">';

        // Dibujar el número del traste base si es mayor a 1
        if ($baseFret > 1) {
            $svg .= "<text x='2' y='36' font-size='10' font-weight='bold' fill='#6b7280'>{$baseFret}fr</text>";
        } else {
            // Cejuela superior (línea gruesa si empieza en el traste 1)
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

        // Dibujar Cejilla si el acorde la requiere
        if ($barre) {
            $fretOffset = ($barre['fret'] - $baseFret) + 1;
            $y = 20 + ($fretOffset * 20) - 10;
            $x1 = 20 + ((6 - $barre['to']) * 14);
            $x2 = 20 + ((6 - $barre['from']) * 14);

            $svg .= "<line x1='{$x1}' y1='{$y}' x2='{$x2}' y2='{$y}' stroke='#2563eb' stroke-width='9' stroke-linecap='round' />";
        }

        // Dibujar marcas de notas (círculos o 'x')
        foreach ($frets as $stringIndex => $fret) {
            $x = 20 + ($stringIndex * 14);

            if ($fret === 'x') {
                $svg .= "<text x='{$x}' y='14' font-size='12' text-anchor='middle' fill='#ef4444' font-weight='bold'>×</text>";
            } elseif ($fret === 0) {
                $svg .= "<circle cx='{$x}' cy='10' r='3.5' stroke='#10b981' stroke-width='1.5' fill='none' />";
            } else {
                // Calcular la posición relativa según el traste base
                $relativeFret = ($fret - $baseFret) + 1;
                $y = 20 + ($relativeFret * 20) - 10;

                // Evitamos redibujar círculos encima de la línea de la cejilla
                if (! $barre || $fret !== $barre['fret']) {
                    $svg .= "<circle cx='{$x}' cy='{$y}' r='4.5' fill='#2563eb' />";
                }
            }
        }

        $svg .= '</svg>';

        return $svg;
    }
}
