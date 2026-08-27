<div
    x-data="{
        editingRaw: @entangle('isEditingRaw'),
        activeChordIndex: null,
        activeLineIndex: null
    }"
    x-on:keydown.window.ctrl.arrow-up.prevent="$wire.transpose(1)"
    x-on:keydown.window.ctrl.arrow-down.prevent="$wire.transpose(-1)"
    class="max-w-5xl mx-auto p-6 space-y-6"
>
    <!-- Encabezado de la Canción y Controles -->
    <div
        class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white dark:bg-zinc-900 p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                {{ $song->title }}
                <flux:badge
                    color="zinc"
                    size="sm"
                >{{ $song->artist ?? 'Artista desconocido' }}</flux:badge>
            </h1>

            <div class="flex items-center gap-4 mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                <span>Tono Original: <strong
                        class="text-zinc-800 dark:text-zinc-200">{{ $song->original_key ?? 'No definido' }}</strong></span>
                <span>Tono Actual: <flux:badge color="accent">{{ $transposition['newKey'] }}</flux:badge></span>
                @if ($song->capo)
                    <span>Capo: <strong>{{ $song->capo }}° traste</strong></span>
                @endif
            </div>
        </div>

        <!-- Barra de Herramientas -->
        <div class="flex items-center gap-2">
            <!-- Transposición -->
            <flux:button.group class="inline-flex">
                <flux:button
                    wire:click="transpose(-1)"
                    icon="minus"
                    size="sm"
                    tooltip="Bajar semitono (Ctrl + ↓)"
                />
                <flux:button
                    wire:click="resetTranspose"
                    size="sm"
                    variant="{{ $semitones !== 0 ? 'subtle' : 'ghost' }}"
                >
                    {{ $semitones > 0 ? "+{$semitones}" : $semitones }} st
                </flux:button>
                <flux:button
                    wire:click="transpose(1)"
                    icon="plus"
                    size="sm"
                    tooltip="Subir semitono (Ctrl + ↑)"
                />
            </flux:button.group>

            <flux:separator
                vertical
                class="my-1"
            />

            <!-- Alternar Modo de Edición -->
            <flux:button
                x-on:click="editingRaw = !editingRaw"
                variant="subtle"
                size="sm"
                icon="code-bracket"
            >
                <span x-text="editingRaw ? 'Modo Visual' : 'Modo ChordPro'"></span>
            </flux:button>

            <!-- Exportar / Opciones -->
            <flux:dropdown>
                <flux:button
                    icon="ellipsis-horizontal"
                    variant="ghost"
                    size="sm"
                />
                <flux:menu>
                    @if ($song->exists)
                        <flux:menu.item
                            href="{{ route('songs.pdf', ['song' => $song, 'semitones' => $semitones]) }}"
                            icon="arrow-down-tray"
                            target="_blank"
                        >
                            Exportar PDF
                        </flux:menu.item>
                        <flux:button
                            href="{{ route('songs.present', $song) }}"
                            target="_blank"
                            variant="subtle"
                            size="sm"
                            icon="presentation-chart-bar"
                        >
                            Presentar
                        </flux:button>
                    @endif
                    <flux:menu.item icon="document-text">
                        Descargar .cho
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>

    <!-- Indicador de Atajos -->
    <div
        class="flex items-center gap-4 text-xs text-zinc-400 bg-zinc-50 dark:bg-zinc-800/50 px-4 py-2 rounded-lg border border-zinc-200/60 dark:border-zinc-800">
        <span class="font-medium text-zinc-500 dark:text-zinc-300">Atajos útiles:</span>
        <span class="flex items-center gap-1"><kbd
                class="px-1.5 py-0.5 bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded shadow-2xs font-mono"
            >Ctrl</kbd> + <kbd
                class="px-1.5 py-0.5 bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded shadow-2xs font-mono"
            >↑ / ↓</kbd> Transponer</span>
    </div>

    <!-- MODO 1: Editor Raw / Texto Plano ChordPro -->
    <div
        x-show="editingRaw"
        x-cloak
        class="space-y-3"
    >
        <flux:textarea
            wire:model.live.debounce.300ms="rawChordPro"
            rows="18"
            class="font-mono text-sm leading-relaxed"
            placeholder="Pega o escribe la canción con formato ChordPro..."
        />
        <div class="flex justify-end">
            <flux:button
                wire:click="save"
                wire:loading.attr="disabled"
                variant="primary"
                icon="check"
            >
                <span wire:loading.remove>Guardar Cambios</span>
                <span wire:loading>Guardando...</span>
            </flux:button>
        </div>
    </div>

    <!-- MODO 2: Renderizado Interactivo y Edición Estructural -->
    <div
        x-show="!editingRaw"
        class="bg-white dark:bg-zinc-900 p-8 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm space-y-8"
    >
        @foreach ($song->sections as $sectionIndex => $section)
            <div
                class="space-y-3 group/section relative border-l-2 border-transparent hover:border-accent-500/30 pl-4 transition-colors">
                <!-- Encabezado de la Sección y Acciones -->
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <span
                            class="text-xs font-bold uppercase tracking-wider text-accent-600 dark:text-accent-400 bg-accent-50 dark:bg-accent-950/50 px-2.5 py-1 rounded"
                        >
                            {{ $section->label ?? $section->type }}
                        </span>
                    </div>

                    <!-- Botones de Acción para la Sección -->
                    <div class="opacity-0 group-hover/section:opacity-100 flex items-center gap-1 transition-opacity">
                        <flux:button
                            wire:click="moveSection({{ $section->id }}, 'up')"
                            size="xs"
                            variant="ghost"
                            icon="chevron-up"
                            tooltip="Mover sección arriba"
                        />
                        <flux:button
                            wire:click="moveSection({{ $section->id }}, 'down')"
                            size="xs"
                            variant="ghost"
                            icon="chevron-down"
                            tooltip="Mover sección abajo"
                        />
                        <flux:button
                            wire:click="deleteSection({{ $section->id }})"
                            wire:confirm="¿Seguro que deseas eliminar esta sección entera?"
                            size="xs"
                            variant="ghost"
                            icon="trash"
                            class="text-red-500 hover:text-red-600"
                            tooltip="Eliminar Sección"
                        />
                    </div>
                </div>

                <!-- Líneas de la Sección -->
                <div class="space-y-3 pl-2">
                    @foreach ($section->lines as $lineIndex => $line)
                        <div
                            class="group/line flex items-center justify-between gap-4 rounded hover:bg-zinc-50 dark:hover:bg-zinc-800/40 p-1 transition-colors">
                            <div class="flex-1">
                                @if ($line->type === 'comment')
                                    <p
                                        class="text-sm italic text-zinc-500 font-sans border-l-2 border-zinc-300 dark:border-zinc-700 pl-2">
                                        {{ $line->content }}
                                    </p>
                                @elseif($line->type === 'tab_line')
                                    <pre class="font-mono text-xs text-zinc-600 dark:text-zinc-400 bg-zinc-50 dark:bg-zinc-950 p-2 rounded overflow-x-auto">{{ $line->content }}</pre>
                                @else
                                    <div class="font-mono text-base leading-relaxed flex flex-wrap gap-y-3 items-end">
                                        {!! $this->renderInteractiveLine($line->content, $sectionIndex, $lineIndex, $section->id, $line->id) !!}
                                    </div>
                                @endif
                            </div>

                            <!-- Controles Rápidos de Línea -->
                            <div
                                class="opacity-0 group-hover/line:opacity-100 flex items-center gap-1 transition-opacity">
                                <flux:button
                                    wire:click="openEditLineModal({{ $line->id }})"
                                    size="xs"
                                    variant="ghost"
                                    icon="pencil-square"
                                    tooltip="Editar texto completo de la línea"
                                />
                                <flux:button
                                    wire:click="moveLine({{ $line->id }}, 'up')"
                                    size="xs"
                                    variant="ghost"
                                    icon="chevron-up"
                                />
                                <flux:button
                                    wire:click="moveLine({{ $line->id }}, 'down')"
                                    size="xs"
                                    variant="ghost"
                                    icon="chevron-down"
                                />
                                <flux:button
                                    wire:click="deleteLine({{ $line->id }})"
                                    size="xs"
                                    variant="ghost"
                                    icon="x-mark"
                                    class="text-zinc-400 hover:text-red-500"
                                />
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Añadir Línea a la Sección -->
                <div class="pt-1">
                    <flux:dropdown>
                        <flux:button
                            size="xs"
                            variant="ghost"
                            icon="plus"
                            class="text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200"
                        >
                            Añadir Línea
                        </flux:button>
                        <flux:menu>
                            <flux:menu.item
                                wire:click="addLine({{ $section->id }}, 'chord_lyrics')"
                                icon="document-text"
                            >
                                Letra y Acordes
                            </flux:menu.item>
                            <flux:menu.item
                                wire:click="addLine({{ $section->id }}, 'comment')"
                                icon="chat-bubble-bottom-center-text"
                            >
                                Comentario / Nota
                            </flux:menu.item>
                            <flux:menu.item
                                wire:click="addLine({{ $section->id }}, 'tab_line')"
                                icon="bars-4"
                            >
                                Tablatura
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>
            </div>
        @endforeach

        <!-- Botón Global para Agregar Nueva Sección -->
        <div class="pt-4 border-t border-zinc-200 dark:border-zinc-800 flex justify-center">
            <flux:button
                wire:click="openAddSectionModal"
                variant="subtle"
                icon="plus"
                size="sm"
            >
                Añadir Nueva Sección
            </flux:button>
        </div>
    </div>

    <!-- MODAL 1: Editar / Insertar Acorde -->
    <flux:modal
        wire:model="showChordModal"
        class="md:w-96"
    >
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Editar Acorde</flux:heading>
                <flux:subheading>Ingresa o modifica el acorde para esta posición</flux:subheading>
            </div>

            <flux:input
                wire:model.defer="selectedChord"
                wire:keydown.enter="updateChord"
                label="Acorde"
                placeholder="ej. G, C#m, D/F#"
                autofocus
            />

            <div class="flex flex-wrap gap-1.5">
                @foreach (['C', 'D', 'E', 'F', 'G', 'A', 'B', 'm', '7', '#', 'b', '/'] as $symbol)
                    <flux:button
                        size="xs"
                        variant="subtle"
                        wire:click="$set('selectedChord', '{{ $selectedChord . $symbol }}')"
                    >
                        {{ $symbol }}
                    </flux:button>
                @endforeach
            </div>

            <div class="flex justify-between items-center pt-2">
                @if (!empty($selectedChord))
                    <flux:button
                        wire:click="removeChord"
                        variant="danger"
                        size="sm"
                        icon="trash"
                    >
                        Quitar Acorde
                    </flux:button>
                @else
                    <div></div>
                @endif

                <div class="flex gap-2">
                    <flux:button
                        wire:click="$set('showChordModal', false)"
                        variant="ghost"
                        size="sm"
                    >
                        Cancelar
                    </flux:button>
                    <flux:button
                        wire:click="updateChord"
                        variant="primary"
                        size="sm"
                    >
                        Guardar
                    </flux:button>
                </div>
            </div>
        </div>
    </flux:modal>

    <!-- MODAL 2: Crear Nueva Sección -->
    <flux:modal
        wire:model="showSectionModal"
        class="md:w-96"
    >
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Añadir Sección</flux:heading>
                <flux:subheading>Elige el tipo y nombre de la sección</flux:subheading>
            </div>

            <flux:select
                wire:model="newSectionType"
                label="Tipo de Sección"
            >
                <option value="verse">Verso</option>
                <option value="chorus">Coro</option>
                <option value="bridge">Puente</option>
                <option value="intro">Introducción</option>
                <option value="outro">Final / Outro</option>
                <option value="solo">Solo</option>
            </flux:select>

            <flux:input
                wire:model="newSectionLabel"
                label="Etiqueta / Nombre de la Sección"
                placeholder="ej. Verso 1, Coro Final"
            />

            <div class="flex justify-end gap-2 pt-2">
                <flux:button
                    wire:click="$set('showSectionModal', false)"
                    variant="ghost"
                    size="sm"
                >
                    Cancelar
                </flux:button>
                <flux:button
                    wire:click="addSection"
                    variant="primary"
                    size="sm"
                >
                    Crear Sección
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- MODAL 3: Editar Texto y Tipo de Línea -->
    <flux:modal
        wire:model="showLineEditModal"
        class="md:w-md"
    >
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Editar Línea</flux:heading>
                <flux:subheading>Modifica el contenido completo o el tipo de esta línea</flux:subheading>
            </div>

            <flux:select
                wire:model="editingLineType"
                label="Tipo de Línea"
            >
                <option value="chord_lyrics">Letra con Acordes (ChordPro)</option>
                <option value="comment">Comentario / Indicación</option>
                <option value="tab_line">Tablatura</option>
            </flux:select>

            <flux:textarea
                wire:model="editingLineContent"
                wire:keydown.enter.prevent="saveLineContent"
                label="Contenido"
                rows="3"
                class="font-mono text-sm"
                placeholder="Escribe el texto o formato ChordPro ej: Te quie[G]ro..."
            />

            <div class="flex justify-end gap-2 pt-2">
                <flux:button
                    wire:click="$set('showLineEditModal', false)"
                    variant="ghost"
                    size="sm"
                >
                    Cancelar
                </flux:button>
                <flux:button
                    wire:click="saveLineContent"
                    variant="primary"
                    size="sm"
                >
                    Guardar Cambios
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
