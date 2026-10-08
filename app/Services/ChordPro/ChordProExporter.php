<?php

namespace App\Services\ChordPro;

use App\Models\Song;

class ChordProExporter
{
    public static function export(Song $song): string
    {
        $content = [];
        $metadataDirectives = [
            'title' => 'title',
            'subtitle' => 'subtitle',
            'artist' => 'artist',
            'original_key' => 'key',
            'capo' => 'capo',
            'tempo' => 'tempo',
            'time_signature' => 'time',
            'duration' => 'duration',
        ];

        foreach ($metadataDirectives as $field => $directive) {
            $value = $song->{$field};
            if ($value !== null && $value !== '') {
                $content[] = '{' . $directive . ': ' . $value . '}';
            }
        }

        $knownDirectives = array_values($metadataDirectives);
        foreach ($song->meta ?? [] as $directive => $value) {
            if (! in_array($directive, $knownDirectives, true) && is_scalar($value)) {
                $content[] = '{' . $directive . ': ' . $value . '}';
            }
        }

        foreach ($song->sections as $section) {
            $startDirective = DirectiveMap::startDirective($section->type);
            $content[] = '{' . $startDirective . ($section->label ? ': ' . $section->label : '') . '}';

            foreach ($section->lines as $line) {
                $content[] = $line->type === 'comment'
                    ? '{comment: ' . $line->content . '}'
                    : $line->content;
            }

            $endDirective = DirectiveMap::endDirective($section->type);
            if ($endDirective !== null) {
                $content[] = '{' . $endDirective . '}';
            }
        }

        return implode("\n", $content);
    }
}
