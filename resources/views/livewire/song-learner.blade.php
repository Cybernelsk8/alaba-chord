<div class="max-w-5xl mx-auto p-6 space-y-6">
    <div
        class="bg-white dark:bg-zinc-900 p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm space-y-6">
        <div class="flex justify-between items-center border-b border-zinc-100 dark:border-zinc-800 pb-4">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $song->title }}</h1>
                <p class="text-sm text-zinc-500">Modo Aprendizaje • Pasa el cursor sobre cualquier acorde para ver su
                    posición en la guitarra.</p>
            </div>
            <flux:badge
                color="accent"
                size="lg"
            >Tono: {{ $song->original_key ?? 'C' }}</flux:badge>
        </div>

        <div class="space-y-8">
            @foreach ($song->sections as $section)
                <div class="space-y-3">
                    <span
                        class="text-xs font-bold uppercase tracking-wider text-accent-600 dark:text-accent-400 bg-accent-50 dark:bg-accent-950/50 px-2.5 py-1 rounded"
                    >
                        {{ $section->label ?? $section->type }}
                    </span>

                    <div class="space-y-4 pl-2">
                        @foreach ($section->lines as $line)
                            @if ($line->type === 'chord_lyrics')
                                <div class="font-mono text-lg leading-relaxed flex flex-wrap gap-y-3 items-end">
                                    {!! $this->renderInteractiveLine($line->content) !!}
                                </div>
                            @elseif ($line->type === 'comment')
                                <p
                                    class="text-sm italic text-zinc-500 font-sans border-l-2 border-zinc-300 dark:border-zinc-700 pl-2">
                                    {{ $line->content }}
                                </p>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
