<?php

namespace App\Services\ChordPro;

use App\Models\Song;
use Illuminate\Support\Facades\DB;

class ChordProImporter
{
    public static function import(string $content, int $userId): Song
    {
        $userId = $userId ?? auth()->id();

        return DB::transaction(function () use ($content, $userId) {
            $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $content));

            $songData = [
                'user_id' => $userId,
                'title' => 'Sin título',
                'meta' => [],
            ];

            $sections = [];
            $currentSection = [
                'type' => 'verse',
                'label' => 'Verso',
                'lines' => [],
            ];

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }

                // 1. Procesar Directivas {directive: value} o {directive}
                if (preg_match('/^\{([^:]+)(?::\s*(.*))?\}$/', $line, $matches)) {
                    $directive = strtolower(trim($matches[1]));
                    $value = isset($matches[2]) ? trim($matches[2]) : null;

                    self::processDirective($directive, $value, $songData, $currentSection, $sections);

                    continue;
                }

                // 2. Procesar Comentarios o Tablatura
                $lineType = 'chord_lyrics';
                if ($currentSection['type'] === 'tab') {
                    $lineType = 'tab_line';
                }

                $currentSection['lines'][] = [
                    'type' => $lineType,
                    'content' => $line,
                ];
            }

            // Guardar la última sección acumulada
            if (! empty($currentSection['lines'])) {
                $sections[] = $currentSection;
            }

            // Inferencia de tono si no venía en el archivo {key: ...}
            if (empty($songData['original_key'])) {
                $songData['original_key'] = ChordTransposer::inferKeyFromContent($content);
            }

            // Persistir en Base de Datos
            $song = Song::create($songData);

            foreach ($sections as $secIndex => $secData) {
                $section = $song->sections()->create([
                    'type' => $secData['type'],
                    'label' => $secData['label'],
                    'position' => $secIndex + 1,
                    'repeats_section_id' => $secData['repeats_section_id'] ?? null,
                ]);

                foreach ($secData['lines'] as $lineIndex => $lineData) {
                    $section->lines()->create([
                        'position' => $lineIndex + 1,
                        'type' => $lineData['type'],
                        'content' => $lineData['content'],
                    ]);
                }
            }

            return $song;
        });
    }

    private static function processDirective(
        string $directive,
        ?string $value,
        array &$songData,
        array &$currentSection,
        array &$sections
    ): void {
        switch ($directive) {
            // Metadatos principales
            case 'title':
            case 't':
                $songData['title'] = $value;
                break;
            case 'subtitle':
            case 'st':
                $songData['subtitle'] = $value;
                break;
            case 'artist':
                $songData['artist'] = $value;
                break;
            case 'key':
                $songData['original_key'] = $value;
                break;
            case 'capo':
                $songData['capo'] = (int) $value;
                break;
            case 'tempo':
                $songData['tempo'] = (int) $value;
                break;
            case 'time':
                $songData['time_signature'] = $value;
                break;

                // Apertura de Entornos (Secciones)
            case 'start_of_chorus':
            case 'soc':
                self::switchSection('chorus', $value ?? 'Coro', $currentSection, $sections);
                break;
            case 'start_of_verse':
            case 'sov':
                self::switchSection('verse', $value ?? 'Verso', $currentSection, $sections);
                break;
            case 'start_of_bridge':
            case 'sob':
                self::switchSection('bridge', $value ?? 'Puente', $currentSection, $sections);
                break;
            case 'start_of_tab':
            case 'sot':
                self::switchSection('tab', $value ?? 'Tablatura', $currentSection, $sections);
                break;

                // Cierre de Entornos
            case 'end_of_chorus':
            case 'eoc':
            case 'end_of_verse':
            case 'eov':
            case 'end_of_bridge':
            case 'eob':
            case 'end_of_tab':
            case 'eot':
                self::switchSection('verse', 'Verso', $currentSection, $sections);
                break;

                // Directivas sueltas (Comentarios)
            case 'comment':
            case 'c':
                $currentSection['lines'][] = [
                    'type' => 'comment',
                    'content' => $value ?? '',
                ];
                break;

                // Directiva no estándar -> a JSON meta
            default:
                $meta = $songData['meta'] ?? [];
                $meta[$directive] = $value;
                $songData['meta'] = $meta;
                break;
        }
    }

    private static function switchSection(
        string $type,
        string $label,
        array &$currentSection,
        array &$sections
    ): void {
        if (! empty($currentSection['lines'])) {
            $sections[] = $currentSection;
        }

        $currentSection = [
            'type' => $type,
            'label' => $label,
            'lines' => [],
        ];
    }
}
