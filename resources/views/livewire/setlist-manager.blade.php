<div class="max-w-7xl mx-auto p-6 grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- PANEL IZQUIERDO: Lista de Repertorios -->
    <div
        class="bg-white dark:bg-zinc-900 p-5 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm space-y-4 h-fit">
        <div class="flex justify-between items-center">
            <h2 class="text-lg font-bold text-zinc-900 dark:text-white">Mis Repertorios</h2>
            <flux:button
                wire:click="openCreateModal"
                size="xs"
                variant="primary"
                icon="plus"
            >Nuevo</flux:button>
        </div>

        <div class="space-y-2">
            @forelse ($setlists as $setlist)
                <div
                    wire:click="selectSetlist({{ $setlist->id }})"
                    class="p-3 rounded-lg border cursor-pointer transition-colors flex justify-between items-center {{ $selectedSetlist?->id === $setlist->id ? 'border-accent-500 bg-accent-50/40 dark:bg-accent-950/20' : 'border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800/50' }}"
                >
                    <div>
                        <h3 class="font-medium text-zinc-900 dark:text-white text-sm">{{ $setlist->title }}</h3>
                        <div class="flex items-center gap-2 text-xs text-zinc-500 mt-1">
                            @if ($setlist->scheduled_at)
                                <span>{{ $setlist->scheduled_at->format('d/m/Y') }}</span>
                            @endif
                            <span>• {{ $setlist->songs_count }} canciones</span>
                        </div>
                    </div>

                    <flux:button
                        wire:click.stop="deleteSetlist({{ $setlist->id }})"
                        wire:confirm="¿Seguro de borrar este repertorio?"
                        size="xs"
                        variant="ghost"
                        icon="trash"
                        class="text-zinc-400 hover:text-red-500"
                    />
                </div>
            @empty
                <p class="text-xs text-zinc-400 text-center py-4">No hay repertorios creados aún.</p>
            @endforelse
        </div>
    </div>

    <!-- PANEL DERECHO: Detalle y Canciones del Repertorio Seleccionado -->
    <div class="lg:col-span-2 space-y-6">
        @if ($selectedSetlist)
            <div
                class="bg-white dark:bg-zinc-900 p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm space-y-6">
                <!-- Header del Setlist -->
                <div class="flex justify-between items-start border-b border-zinc-100 dark:border-zinc-800 pb-4">
                    <div>
                        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $selectedSetlist->title }}</h1>
                        @if ($selectedSetlist->description)
                            <p class="text-sm text-zinc-500 mt-1">{{ $selectedSetlist->description }}</p>
                        @endif
                        @if ($selectedSetlist->scheduled_at)
                            <span
                                class="inline-block mt-2 text-xs bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 px-2.5 py-1 rounded"
                            >
                                Fecha: {{ $selectedSetlist->scheduled_at->format('d/m/Y') }}
                            </span>
                        @endif
                    </div>

                    @if ($selectedSetlist)
                        <div class="flex items-center gap-2">
                            <!-- Descargar Cancionero Completo en PDF -->
                            <flux:button
                                href="{{ route('setlists.pdf', $selectedSetlist->id) }}"
                                target="_blank"
                                variant="subtle"
                                icon="arrow-down-tray"
                                size="sm"
                            >
                                Exportar Cancionero (PDF)
                            </flux:button>

                            <!-- Botón de Modo Ensayo -->
                            <flux:button
                                href="{{ route('setlists.view', $selectedSetlist->id) }}"
                                icon="play"
                                variant="primary"
                                size="sm"
                            >
                                Modo Ensayo
                            </flux:button>
                        </div>
                    @endif



                    <flux:button
                        wire:click="$set('showSongPickerModal', true)"
                        variant="primary"
                        icon="plus"
                        size="sm"
                    >
                        Agregar Canción
                    </flux:button>


                </div>

                <!-- Lista de Canciones en el Setlist -->
                <div class="space-y-3">
                    @forelse ($selectedSetlist->songs as $song)
                        <div
                            class="flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200 dark:border-zinc-800 rounded-lg group">
                            <div class="flex items-center gap-3">
                                <span class="font-bold text-xs text-zinc-400 w-5">{{ $song->pivot->position }}.</span>
                                <div>
                                    <h4
                                        class="font-semibold text-zinc-900 dark:text-zinc-100 text-sm flex items-center gap-2">
                                        {{ $song->title }}
                                        <flux:badge
                                            size="sm"
                                            color="accent"
                                        >{{ $song->pivot->custom_key ?? ($song->original_key ?? 'C') }}</flux:badge>
                                    </h4>
                                    <p class="text-xs text-zinc-500">{{ $song->artist }}</p>
                                    @if ($song->pivot->notes)
                                        <p class="text-xs italic text-accent-600 dark:text-accent-400 mt-0.5">Nota:
                                            {{ $song->pivot->notes }}</p>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center gap-1 opacity-90">
                                <flux:button
                                    wire:click="openPivotModal({{ $song->pivot->id }}, '{{ $song->pivot->custom_key }}', '{{ $song->pivot->notes }}')"
                                    size="xs"
                                    variant="ghost"
                                    icon="pencil"
                                    tooltip="Editar tono o nota"
                                />
                                <flux:button
                                    wire:click="moveSong({{ $song->id }}, 'up')"
                                    size="xs"
                                    variant="ghost"
                                    icon="chevron-up"
                                />
                                <flux:button
                                    wire:click="moveSong({{ $song->id }}, 'down')"
                                    size="xs"
                                    variant="ghost"
                                    icon="chevron-down"
                                />
                                <flux:button
                                    wire:click="removeSongFromSetlist({{ $song->id }})"
                                    size="xs"
                                    variant="ghost"
                                    icon="x-mark"
                                    class="text-red-500"
                                />
                            </div>
                        </div>
                    @empty
                        <div
                            class="text-center py-8 text-zinc-400 text-sm border-2 border-dashed border-zinc-200 dark:border-zinc-800 rounded-lg">
                            Este repertorio aún no tiene canciones agregadas.
                        </div>
                    @endforelse
                </div>
            </div>
        @else
            <div
                class="bg-white dark:bg-zinc-900 p-12 rounded-xl border border-zinc-200 dark:border-zinc-800 text-center text-zinc-400">
                Selecciona o crea un repertorio a la izquierda para comenzar a organizarlo.
            </div>
        @endif
    </div>

    <!-- MODAL 1: Crear Repertorio -->
    <flux:modal
        wire:model="showCreateModal"
        class="md:w-96"
    >
        <div class="space-y-4">
            <flux:heading size="lg">Nuevo Repertorio</flux:heading>

            <flux:input
                wire:model="title"
                label="Título del Evento / Lista"
                placeholder="ej. Servicio Domingo Mañana"
            />
            <flux:textarea
                wire:model="description"
                label="Descripción / Observaciones"
                rows="2"
            />
            <flux:input
                type="date"
                wire:model="scheduled_at"
                label="Fecha Programada"
            />

            <div class="flex justify-end gap-2 pt-2">
                <flux:button
                    wire:click="$set('showCreateModal', false)"
                    variant="ghost"
                    size="sm"
                >Cancelar</flux:button>
                <flux:button
                    wire:click="createSetlist"
                    variant="primary"
                    size="sm"
                >Crear</flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- MODAL 2: Buscador / Selector de Canciones -->
    <flux:modal
        wire:model="showSongPickerModal"
        class="md:w-md"
    >
        <div class="space-y-4">
            <flux:heading size="lg">Agregar Canción al Repertorio</flux:heading>

            <flux:input
                wire:model.live.debounce.300ms="searchSong"
                placeholder="Buscar por título o artista..."
                icon="magnifying-glass"
                autofocus
            />

            <div class="space-y-2 max-h-60 overflow-y-auto">
                @forelse ($availableSongs as $song)
                    <div class="flex items-center justify-between p-2 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800">
                        <div>
                            <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $song->title }}
                            </div>
                            <div class="text-xs text-zinc-500">{{ $song->artist ?? 'Sin artista' }} • Tono:
                                {{ $song->original_key ?? 'C' }}</div>
                        </div>
                        <flux:button
                            wire:click="addSongToSetlist({{ $song->id }})"
                            size="xs"
                            variant="subtle"
                            icon="plus"
                        >Agregar</flux:button>
                    </div>
                @empty
                    <p class="text-xs text-zinc-400 text-center py-4">No se encontraron canciones disponibles.</p>
                @endforelse
            </div>

            <div class="flex justify-end pt-2">
                <flux:button
                    wire:click="$set('showSongPickerModal', false)"
                    variant="ghost"
                    size="sm"
                >Cerrar</flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- MODAL 3: Ajustar Tono Personalizado y Notas por Canción -->
    <flux:modal
        wire:model="showPivotModal"
        class="md:w-96"
    >
        <div class="space-y-4">
            <flux:heading size="lg">Ajustes para esta presentación</flux:heading>

            <flux:input
                wire:model="customKey"
                label="Tono de interpretación"
                placeholder="ej. G#, Bb"
            />
            <flux:textarea
                wire:model="notes"
                label="Nota específica"
                placeholder="ej. Repetir coro 2 veces al final"
                rows="2"
            />

            <div class="flex justify-end gap-2 pt-2">
                <flux:button
                    wire:click="$set('showPivotModal', false)"
                    variant="ghost"
                    size="sm"
                >Cancelar</flux:button>
                <flux:button
                    wire:click="updatePivotDetails"
                    variant="primary"
                    size="sm"
                >Guardar Ajustes</flux:button>
            </div>
        </div>
    </flux:modal>

</div>
