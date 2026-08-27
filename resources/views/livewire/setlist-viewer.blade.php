<div
    x-data
    x-on:keydown.window.arrow-right.prevent="$wire.nextSong()"
    x-on:keydown.window.arrow-left.prevent="$wire.previousSong()"
    class="max-w-5xl mx-auto p-6 space-y-6"
>
    <!-- BARRA SUPERIOR DE NAVEGACIÓN -->
    <div
        class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white dark:bg-zinc-900 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm sticky top-4 z-10">
        <div>
            <h1 class="text-xl font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                {{ $setlist->title }}
            </h1>
            <p class="text-xs text-zinc-500">
                Canción {{ $currentIndex + 1 }} de {{ $setlist->songs->count() }}
            </p>
        </div>

        <!-- Controles de Navegación del Repertorio -->
        <div class="flex items-center gap-2">
            <flux:button
                wire:click="previousSong"
                icon="chevron-left"
                size="sm"
                variant="subtle"
                :disabled="$currentIndex === 0"
                tooltip="Anterior (←)"
            />

            <!-- Selector desplegable de canciones del setlist -->
            <flux:dropdown>
                <flux:button
                    size="sm"
                    variant="ghost"
                    icon-trailing="chevron-down"
                >
                    {{ $currentSong ? $currentSong->title : 'Seleccionar Canción' }}
                </flux:button>
                <flux:menu>
                    @foreach ($setlist->songs as $index => $s)
                        <flux:menu.item wire:click="selectSongIndex({{ $index }})">
                            <span class="font-mono text-xs text-zinc-400 mr-2">{{ $index + 1 }}.</span>
                            {{ $s->title }}
                            <flux:badge
                                size="sm"
                                class="ml-2"
                            >{{ $s->pivot->custom_key ?? $s->original_key }}</flux:badge>
                        </flux:menu.item>
                    @endforeach
                </flux:menu>
            </flux:dropdown>

            <flux:button
                wire:click="nextSong"
                icon-trailing="chevron-right"
                size="sm"
                variant="subtle"
                :disabled="$currentIndex === $setlist->songs->count() - 1"
                tooltip="Siguiente (→)"
            />

            <flux:separator
                vertical
                class="my-1"
            />

            <!-- Transposición rápida -->
            <flux:button.group class="inline-flex">
                <flux:button
                    wire:click="transpose(-1)"
                    icon="minus"
                    size="sm"
                />
                <flux:button
                    wire:click="$set('semitones', 0)"
                    size="sm"
                    variant="ghost"
                >
                    {{ $semitones > 0 ? "+{$semitones}" : $semitones }} st
                </flux:button>
                <flux:button
                    wire:click="transpose(1)"
                    icon="plus"
                    size="sm"
                />
            </flux:button.group>
        </div>
    </div>

    <!-- CONTENIDO DE LA CANCIÓN ACTUAL -->
    @if ($currentSong)
        <div
            class="bg-white dark:bg-zinc-900 p-8 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm space-y-8">
            <!-- Header Canción -->
            <div class="border-b border-zinc-100 dark:border-zinc-800 pb-4 flex justify-between items-start">
                <div>
                    <h2 class="text-3xl font-bold text-zinc-900 dark:text-white">{{ $currentSong->title }}</h2>
                    <p class="text-sm text-zinc-500 mt-1">{{ $currentSong->artist ?? 'Artista Desconocido' }}</p>
                </div>

                <div class="flex items-center gap-3 text-sm">
                    <span class="text-zinc-500">Tono: <flux:badge
                            color="accent"
                            size="lg"
                        >{{ $currentSong->pivot->custom_key ?? ($currentSong->original_key ?? 'C') }}</flux:badge></span>
                    @if ($currentSong->capo)
                        <span class="text-zinc-500">Capo: <strong>{{ $currentSong->capo }}°</strong></span>
                    @endif
                </div>
            </div>

            <!-- Nota especial para el concierto/servicio -->
            @if ($currentSong->pivot->notes)
                <div
                    class="bg-accent-50/50 dark:bg-accent-950/30 border-l-4 border-accent-500 p-3 rounded-r-lg text-sm text-accent-900 dark:text-accent-200">
                    <strong>Nota para la banda:</strong> {{ $currentSong->pivot->notes }}
                </div>
            @endif

            <!-- Estructura de la Canción -->
            <div class="space-y-6">
                @foreach ($currentSong->sections as $section)
                    <div class="space-y-3">
                        <span
                            class="text-xs font-bold uppercase tracking-wider text-accent-600 dark:text-accent-400 bg-accent-50 dark:bg-accent-950/50 px-2.5 py-1 rounded"
                        >
                            {{ $section->label ?? $section->type }}
                        </span>

                        <div class="space-y-3 pl-2">
                            @foreach ($section->lines as $line)
                                @if ($line->type === 'comment')
                                    <p
                                        class="text-sm italic text-zinc-500 font-sans border-l-2 border-zinc-300 dark:border-zinc-700 pl-2">
                                        {{ $line->content }}
                                    </p>
                                @elseif($line->type === 'tab_line')
                                    <pre class="font-mono text-xs text-zinc-600 dark:text-zinc-400 bg-zinc-50 dark:bg-zinc-950 p-2 rounded overflow-x-auto">{{ $line->content }}</pre>
                                @else
                                    <div class="font-mono text-lg leading-relaxed flex flex-wrap gap-y-3 items-end">
                                        {!! $this->renderInteractiveLine($line->content) !!}
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div
            class="bg-white dark:bg-zinc-900 p-12 rounded-xl text-center text-zinc-400 border border-zinc-200 dark:border-zinc-800">
            No hay canciones seleccionadas en este repertorio.
        </div>
    @endif
</div>
