<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Repertorio - {{ $setlist->title }}</title>
    <style>
        body {
            font-family: sans-serif;
            margin: 20px;
            color: #1f2937;
        }

        .page-break {
            page-break-after: always;
        }

        /* Estilos de la Portada / Índice */
        .setlist-header {
            border-bottom: 3px solid #2563eb;
            padding-bottom: 12px;
            margin-bottom: 25px;
        }

        .setlist-title {
            font-size: 26px;
            font-weight: bold;
            margin: 0;
            color: #111827;
        }

        .setlist-meta {
            font-size: 13px;
            color: #4b5563;
            margin-top: 6px;
        }

        .index-table {
            w-full;
            border-collapse: collapse;
            margin-top: 15px;
            width: 100%;
        }

        .index-table th {
            text-align: left;
            border-bottom: 2px solid #e5e7eb;
            padding: 8px;
            font-size: 12px;
            color: #6b7280;
        }

        .index-table td {
            padding: 8px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 13px;
        }

        /* Estilos de cada Canción */
        .song-header {
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }

        .song-title {
            font-size: 20px;
            font-weight: bold;
            margin: 0;
        }

        .song-meta {
            font-size: 11px;
            color: #6b7280;
            margin-top: 4px;
        }

        .band-note {
            background: #eff6ff;
            border-left: 3px solid #2563eb;
            padding: 6px 10px;
            font-size: 11px;
            color: #1e40af;
            margin-bottom: 15px;
        }

        .section-label {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: #2563eb;
            background: #eff6ff;
            padding: 2px 5px;
            border-radius: 3px;
            display: inline-block;
            margin-top: 12px;
            margin-bottom: 6px;
        }

        .line-comment {
            font-style: italic;
            color: #6b7280;
            font-size: 11px;
            margin: 3px 0;
        }

        .line-tab {
            font-family: monospace;
            font-size: 9px;
            background: #f3f4f6;
            padding: 4px;
            margin: 3px 0;
        }
    </style>
</head>

<body>

    <!-- PORTADA / ÍNDICE DEL REPERTORIO -->
    <div class="setlist-header">
        <h1 class="setlist-title">{{ $setlist->title }}</h1>
        <div class="setlist-meta">
            @if ($setlist->scheduled_at)
                Fecha: <strong>{{ $setlist->scheduled_at->format('d/m/Y') }}</strong> |
            @endif
            Total de Canciones: <strong>{{ count($songsData) }}</strong>
        </div>
        @if ($setlist->description)
            <p style="font-size: 12px; color: #6b7280; margin-top: 8px;">{{ $setlist->description }}</p>
        @endif
    </div>

    <h3 style="font-size: 14px; margin-bottom: 10px;">Índice de Canciones</h3>
    <table class="index-table">
        <thead>
            <tr>
                <th style="width: 30px;">#</th>
                <th>Título</th>
                <th>Artista</th>
                <th style="width: 80px;">Tono</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($songsData as $index => $item)
                <tr>
                    <td><strong>{{ $index + 1 }}</strong></td>
                    <td>{{ $item['song']->title }}</td>
                    <td>{{ $item['song']->artist ?? '-' }}</td>
                    <td><strong>{{ $item['customKey'] ?? ($item['song']->original_key ?? 'C') }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="page-break"></div>

    <!-- CANCIONES INDIVIDUALES (Una por página) -->
    @foreach ($songsData as $index => $item)
        @php
            $song = $item['song'];
        @endphp

        <div class="song-header">
            <h2 class="song-title">{{ $index + 1 }}. {{ $song->title }}</h2>
            <div class="song-meta">
                Artista: {{ $song->artist ?? 'Desconocido' }} |
                Tono de Interpretación: <strong>{{ $item['customKey'] ?? ($song->original_key ?? 'C') }}</strong>
                @if ($song->capo)
                    | Capo: {{ $song->capo }}° traste
                @endif
            </div>
        </div>

        @if (!empty($item['notes']))
            <div class="band-note">
                <strong>Nota para el equipo:</strong> {{ $item['notes'] }}
            </div>
        @endif

        @foreach ($item['htmlContent'] as $section)
            <div>
                <div class="section-label">{{ $section['label'] }}</div>
                <div>
                    @foreach ($section['lines'] as $line)
                        @if ($line['type'] === 'chord_lyrics')
                            {!! $line['html'] !!}<br>
                        @elseif($line['type'] === 'comment')
                            <div class="line-comment">{{ $line['content'] }}</div>
                        @elseif($line['type'] === 'tab_line')
                            <div class="line-tab">{{ $line['content'] }}</div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach

        @if (!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach

</body>

</html>
