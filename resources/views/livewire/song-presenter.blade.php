<div
    x-data="{
        isFullscreen: false,
        toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen();
                this.isFullscreen = true;
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                    this.isFullscreen = false;
                }
            }
        }
    }"
    x-on:keydown.window.arrow-right.prevent="$wire.nextSection()"
    x-on:keydown.window.space.prevent="$wire.nextSection()"
    x-on:keydown.window.arrow-left.prevent="$wire.previousSection()"
    class="min-h-screen bg-black text-white flex flex-col justify-between p-8 select-none"
>
    <!-- BARRA SUPERIOR DE CONTROLES (Flotante y discreta) -->
    <div
        class="flex justify-between items-center opacity-30 hover:opacity-100 transition-opacity bg-zinc-900/80 p-3 rounded-xl border border-zinc-800">
        <div class="flex items-center gap-3">
            <span class="font-bold text-lg text-zinc-200">{{ $song->title }}</span>
            <flux:badge
                size="sm"
                color="zinc"
            >{{ $song->artist }}</flux:badge>
        </div>

        <div class="flex items-center gap-2">
            <!-- Alternar Acordes / Solo Letra -->
            <flux:button
                wire:click="toggleChords"
                size="xs"
                variant="{{ $showChords ? 'primary' : 'subtle' }}"
            >
                {{ $showChords ? 'Con Acordes' : 'Solo Letra' }}
            </flux:button>

            <!-- Control de Zoom de Texto -->
            <flux:button.group>
                <flux:button
                    wire:click="adjustFontSize(-2)"
                    size="xs"
                    icon="minus"
                />
                <flux:button
                    size="xs"
                    variant="ghost"
                >{{ $fontSize }}px</flux:button>
                <flux:button
                    wire:click="adjustFontSize(2)"
                    size="xs"
                    icon="plus"
                />
            </flux:button.group>

            <!-- Fullscreen -->
            <flux:button
                x-on:click="toggleFullscreen"
                size="xs"
                variant="subtle"
                icon="arrows-pointing-out"
            />
        </div>
    </div>

    <!-- CONTENIDO DE LA SECCIÓN ACTUAL (CENTRADO EN PANTALLA) -->
    <div class="my-auto text-center space-y-8 max-w-6xl mx-auto w-full">
        @if ($currentSection)
            <div
                class="inline-block bg-accent-600/20 text-accent-400 border border-accent-500/30 font-bold uppercase tracking-widest px-4 py-1.5 rounded-full text-sm">
                {{ $currentSection->label ?? $currentSection->type }}
            </div>

            <div
                class="space-y-6 font-mono leading-relaxed"
                style="font-size: {{ $fontSize }}px;"
            >
                @foreach ($currentSection->lines as $line)
                    @if ($line->type === 'chord_lyrics')
                        <div class="flex flex-wrap justify-center gap-y-4 items-end">
                            @if ($showChords)
                                {!! $this->renderInteractiveLine($line->content) !!}
                            @else
                                <span class="text-white">{{ $this->renderLyricsOnly($line->content) }}</span>
                            @endif
                        </div>
                    @elseif ($line->type === 'comment')
                        <p class="text-zinc-500 italic text-2xl font-sans">{{ $line->content }}</p>
                    @elseif ($line->type === 'tab_line')
                        <pre class="text-left font-mono text-base bg-zinc-900 p-4 rounded overflow-x-auto text-accent-400">{{ $line->content }}</pre>
                    @endif
                @endforeach
            </div>
        @endif
    </div>

    <!-- BARRA INFERIOR DE PROGRESO -->
    <div class="flex justify-between items-center text-zinc-500 text-xs pt-4 border-t border-zinc-900">
        <div>
            Sección {{ $currentSectionIndex + 1 }} de {{ $song->sections->count() }}
        </div>
        <div class="flex gap-4">
            <span><kbd class="px-1.5 py-0.5 bg-zinc-800 rounded">Espacio / →</kbd> Siguiente</span>
            <span><kbd class="px-1.5 py-0.5 bg-zinc-800 rounded">←</kbd> Anterior</span>
        </div>
    </div>
</div>
