<div class="max-w-7xl mx-auto p-6 space-y-6">

    <!-- CABECERA Y FILTROS -->
    <div
        class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white dark:bg-zinc-900 p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Catálogo de Canciones</h1>
            <p class="text-sm text-zinc-500">Gestiona, busca y organiza todas las canciones de tu repertorio.</p>
        </div>

        <flux:button
            wire:click="openCreateModal"
            variant="primary"
            icon="plus"
        >
            Nueva Canción
        </flux:button>
    </div>

    <!-- BARRA DE BÚSQUEDA Y FILTROS -->
    <div class="flex flex-col md:flex-row gap-4">
        <div class="flex-1">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Buscar por título o artista..."
                icon="magnifying-glass"
            />
        </div>

        <div class="w-full md:w-48">
            <flux:select
                wire:model.live="filterKey"
                placeholder="Todos los tonos"
            >
                <option value="">Todos los tonos</option>
                @foreach ($availableKeys as $key)
                    <option value="{{ $key }}">{{ $key }}</option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <!-- GRID DE CANCIONES -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($songs as $song)
            <div
                class="bg-white dark:bg-zinc-900 p-5 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm flex flex-col justify-between hover:border-zinc-300 dark:hover:border-zinc-700 transition-colors">
                <div class="space-y-2">
                    <div class="flex justify-between items-start gap-2">
                        <h3 class="font-bold text-lg text-zinc-900 dark:text-white line-clamp-1">{{ $song->title }}
                        </h3>
                        <flux:badge
                            color="accent"
                            size="sm"
                        >{{ $song->original_key ?? 'C' }}</flux:badge>
                    </div>

                    <p class="text-sm text-zinc-500 line-clamp-1">{{ $song->artist ?? 'Artista no especificado' }}</p>

                    <div class="flex items-center gap-3 text-xs text-zinc-400 pt-2">
                        @if ($song->tempo)
                            <span>BPM: <strong>{{ $song->tempo }}</strong></span>
                        @endif
                        @if ($song->capo)
                            <span>Capo: <strong>{{ $song->capo }}°</strong></span>
                        @endif
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="flex items-center justify-between pt-4 mt-4 border-t border-zinc-100 dark:border-zinc-800">
                    <div class="flex items-center gap-1">
                        <flux:button
                            href="{{ route('songs.edit', $song) }}"
                            size="xs"
                            variant="ghost"
                            icon="pencil-square"
                            tooltip="Editar Canción"
                        />
                        <flux:button
                            href="{{ route('songs.present', $song) }}"
                            target="_blank"
                            size="xs"
                            variant="ghost"
                            icon="presentation-chart-bar"
                            tooltip="Modo Presentador"
                        />
                        <flux:button
                            href="{{ route('songs.learn', $song) }}"
                            size="xs"
                            variant="ghost"
                            icon="academic-cap"
                            tooltip="Modo Aprendizaje (Acordes)"
                        />
                        <flux:button
                            href="{{ route('songs.pdf', $song) }}"
                            target="_blank"
                            size="xs"
                            variant="ghost"
                            icon="arrow-down-tray"
                            tooltip="Descargar PDF"
                        />
                    </div>

                    <flux:button
                        wire:click="deleteSong({{ $song->id }})"
                        wire:confirm="¿Seguro que deseas eliminar esta canción?"
                        size="xs"
                        variant="ghost"
                        icon="trash"
                        class="text-zinc-400 hover:text-red-500"
                    />
                </div>
            </div>
        @empty
            <div
                class="col-span-full bg-white dark:bg-zinc-900 p-12 rounded-xl border border-zinc-200 dark:border-zinc-800 text-center text-zinc-400">
                No se encontraron canciones que coincidan con la búsqueda.
            </div>
        @endforelse
    </div>

    <!-- PAGINACIÓN -->
    <div class="pt-4">
        {{ $songs->links() }}
    </div>

    <!-- MODAL: CREAR CANCIÓN RÁPIDA -->
    <flux:modal
        wire:model="showCreateModal"
        class="md:w-96"
    >
        <div class="space-y-4">
            <flux:heading size="lg">Nueva Canción</flux:heading>

            <flux:input
                wire:model="title"
                label="Título"
                placeholder="ej. Cuan Grande es Él"
            />
            <flux:input
                wire:model="artist"
                label="Artista / Autor"
                placeholder="ej. En Spirit and Truth"
            />

            <div class="grid grid-cols-2 gap-3">
                <flux:select
                    wire:model="original_key"
                    label="Tono Original"
                >
                    @foreach ($availableKeys as $key)
                        <option value="{{ $key }}">{{ $key }}</option>
                    @endforeach
                </flux:select>

                <flux:input
                    type="number"
                    wire:model="tempo"
                    label="Tempo (BPM)"
                    placeholder="ej. 72"
                />
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <flux:button
                    wire:click="$set('showCreateModal', false)"
                    variant="ghost"
                    size="sm"
                >Cancelar</flux:button>
                <flux:button
                    wire:click="createSong"
                    variant="primary"
                    size="sm"
                >Crear y Editar</flux:button>
            </div>
        </div>
    </flux:modal>

</div>
