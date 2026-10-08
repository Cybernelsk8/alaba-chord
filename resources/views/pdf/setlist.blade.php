<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Repertorio - {{ $setlist->title }}</title>
    <style>
        @page {
            margin: 8mm;
        }

        body {
            font-family: "DejaVu Sans", sans-serif;
            margin: 0;
            color: #1f2937;
        }

        .pdf-page {
            width: 100%;
        }

        .page-break-before {
            page-break-before: always;
        }

        .setlist-header {
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 12px;
        }

        .setlist-title {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
            color: #111827;
        }

        .setlist-meta {
            font-size: 11px;
            color: #4b5563;
            margin-top: 5px;
        }

        .cover-content {
            margin-top: 150px;
        }

        .cover-description {
            max-width: 520px;
            margin-top: 14px;
            font-size: 11px;
            line-height: 1.5;
            color: #475569;
        }

        .index-title {
            margin: 0 0 8px;
            font-size: 14px;
            color: #111827;
        }

        .index-table {
            border-collapse: collapse;
            table-layout: fixed;
            width: 100%;
            margin-bottom: 7px;
        }

        .index-table td {
            width: 50%;
            height: 11px;
            padding: 2px 5px 2px 0;
            font-size: 7px;
            border-bottom: 1px solid #f1f5f9;
            white-space: nowrap;
            overflow: hidden;
        }

        .page-label {
            float: right;
            color: #64748b;
            font-size: 7px;
        }

        .columns {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .columns td {
            width: 50%;
            vertical-align: top;
            padding: 0 4mm 0 0;
        }

        .columns td+td {
            border-left: 1px solid #e2e8f0;
            padding: 0 0 0 4mm;
        }

        .song-block {
            page-break-inside: avoid;
            margin-bottom: 5px;
        }

        .song-heading {
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 2px;
            margin-bottom: 3px;
        }

        .song-title {
            font-size: 9px;
            font-weight: bold;
            margin: 0;
        }

        .song-meta {
            font-size: 7px;
            color: #6b7280;
            margin-top: 2px;
        }

        .song-continuation {
            font-size: 7px;
            font-weight: bold;
            color: #475569;
            margin: 2px 0;
        }

        .band-note {
            background: #eff6ff;
            border-left: 2px solid #2563eb;
            padding: 3px 5px;
            font-size: 7px;
            color: #1e40af;
            margin-bottom: 4px;
        }

        .section-label {
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
            color: #2563eb;
            margin-top: 4px;
            margin-bottom: 1px;
        }

        .line-comment {
            font-style: italic;
            color: #6b7280;
            font-size: 7px;
            margin: 1px 0;
        }

        .line-tab {
            font-family: monospace;
            font-size: 6px;
            background: #f3f4f6;
            padding: 2px;
            margin: 1px 0;
        }

        .line-break {
            height: 1px;
        }
    </style>
</head>

<body>
    @foreach ($pages as $page)
        <div class="pdf-page {{ $page['pageNumber'] > 1 ? 'page-break-before' : '' }}">
            @if ($page['type'] === 'cover')
                <div class="cover-content">
                    <div class="setlist-header">
                        <h1 class="setlist-title">{{ $setlist->title }}</h1>
                        <div class="setlist-meta">
                            @if ($setlist->scheduled_at)
                                Fecha: <strong>{{ $setlist->scheduled_at->format('d/m/Y') }}</strong>
                            @endif
                        </div>
                    </div>
                    @if ($setlist->description)
                        <p class="cover-description">{{ $setlist->description }}</p>
                    @endif
                </div>
            @elseif ($page['type'] === 'index')
                <span class="page-label">Página {{ $page['pageNumber'] }} / {{ count($pages) }}</span>
                <h1 class="index-title">Índice de canciones</h1>
                <table class="index-table">
                    <tbody>
                        @foreach ($page['rows'] as $row)
                            <tr>
                                @foreach ($row as $item)
                                    <td>
                                        <strong>{{ $item['number'] }}.</strong>
                                        {{ $item['song']->title }}
                                        @if ($item['song']->artist)
                                            <span style="color: #64748b">| {{ $item['song']->artist }}</span>
                                        @endif
                                        <span style="color: #64748b">({{ $item['key'] }})</span>
                                    </td>
                                @endforeach
                                @if (count($row) === 1)
                                    <td></td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <span class="page-label">Página {{ $page['pageNumber'] }} / {{ count($pages) }}</span>
                <table class="columns">
                    <tbody>
                        <tr>
                            @foreach ($page['columns'] as $column)
                                <td>
                                    @foreach ($column as $block)
                                        <div class="song-block">
                                            @if ($block['startsSong'])
                                                <div class="song-heading">
                                                    <h2 class="song-title">{{ $block['songNumber'] }}.
                                                        {{ $block['title'] }}</h2>
                                                    <div class="song-meta">
                                                        {{ $block['artist'] ?? 'Artista desconocido' }} |
                                                        Tono: <strong>{{ $block['key'] }}</strong>
                                                        @if ($block['capo'])
                                                            | Capo: {{ $block['capo'] }}°
                                                        @endif
                                                    </div>
                                                </div>
                                                @if ($block['notes'])
                                                    <div class="band-note">{{ $block['notes'] }}</div>
                                                @endif
                                            @else
                                                <div class="song-continuation">{{ $block['songNumber'] }}.
                                                    {{ $block['title'] }} (continúa)</div>
                                            @endif

                                            @if ($block['sectionLabel'])
                                                <div class="section-label">{{ $block['sectionLabel'] }}</div>
                                            @endif

                                            @foreach ($block['lines'] as $line)
                                                @if ($line['type'] === 'chord_lyrics')
                                                    {!! $line['html'] !!}<br>
                                                @elseif ($line['type'] === 'comment')
                                                    <div class="line-comment">{{ $line['content'] }}</div>
                                                @elseif ($line['type'] === 'tab_line')
                                                    <div class="line-tab">{{ $line['content'] }}</div>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endforeach
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            @endif
        </div>
    @endforeach
</body>

</html>
