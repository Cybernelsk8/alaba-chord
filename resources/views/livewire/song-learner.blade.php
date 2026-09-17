<div class="max-w-5xl mx-auto p-6 space-y-6">
    <div
        class="bg-white dark:bg-zinc-900 p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm space-y-6">
        <div
            class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-100 dark:border-zinc-800 pb-4">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $song->title }}</h1>
                <p class="text-sm text-zinc-500">Modo Aprendizaje • Pasa el cursor sobre cualquier acorde para ver su
                    posición en la guitarra.</p>
            </div>

            <div class="flex items-center gap-3">
                <!-- Controles para Transportar -->
                <div
                    class="flex items-center bg-zinc-100 dark:bg-zinc-800 rounded-lg p-1 border border-zinc-200 dark:border-zinc-700">
                    <button
                        wire:click="transpose(-1)"
                        class="px-2.5 py-1 text-xs font-bold text-zinc-700 dark:text-zinc-300 hover:bg-white dark:hover:bg-zinc-700 rounded transition"
                        title="Bajar medio tono"
                    >-</button>

                    <span class="px-2 text-xs font-semibold text-zinc-600 dark:text-zinc-400 select-none">
                        {{ $semitones > 0 ? '+' . $semitones : $semitones }}
                    </span>

                    <button
                        wire:click="transpose(1)"
                        class="px-2.5 py-1 text-xs font-bold text-zinc-700 dark:text-zinc-300 hover:bg-white dark:hover:bg-zinc-700 rounded transition"
                        title="Subir medio tono"
                    >+</button>

                    @if ($semitones !== 0)
                        <button
                            wire:click="resetTranspose"
                            class="ml-1 px-2 py-1 text-[10px] font-bold text-red-500 hover:bg-red-50 dark:hover:bg-red-950/50 rounded transition"
                            title="Restablecer tono original"
                        >Reset</button>
                    @endif
                </div>

                <!-- Insignia del Tono Actual -->
                <flux:badge
                    color="accent"
                    size="lg"
                >
                    Tono: {{ $this->transposedKey }}
                </flux:badge>
            </div>
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
