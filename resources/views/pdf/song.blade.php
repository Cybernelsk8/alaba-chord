<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>{{ $song->title }}</title>
    <style>
        body {
            font-family: sans-serif;
            margin: 20px;
            color: #1f2937;
        }

        .header {
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .title {
            font-size: 22px;
            font-weight: bold;
            margin: 0;
        }

        .subtitle {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
        }

        .section-label {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            color: #2563eb;
            background: #eff6ff;
            padding: 3px 6px;
            border-radius: 4px;
            display: inline-block;
            margin-top: 15px;
            margin-bottom: 8px;
        }

        .line-comment {
            font-style: italic;
            color: #6b7280;
            font-size: 12px;
            margin: 4px 0;
        }

        .line-tab {
            font-family: monospace;
            font-size: 10px;
            background: #f3f4f6;
            padding: 4px;
            margin: 4px 0;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1 class="title">{{ $song->title }}</h1>
        <div class="subtitle">
            Artista: {{ $song->artist ?? 'Desconocido' }} |
            Tono: {{ $song->original_key ?? 'C' }}
            @if ($song->capo)
                | Capo: {{ $song->capo }}° traste
            @endif
        </div>
    </div>

    @foreach ($htmlContent as $section)
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
</body>

</html>
