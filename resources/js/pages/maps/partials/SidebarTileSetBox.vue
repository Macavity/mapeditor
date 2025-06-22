<script setup lang="ts">
import { useTileSelection } from '@/composables/useTileSelection';
import { useEditorStore } from '@/stores/editorStore';
import { useTileSetStore } from '@/stores/tileSetStore';
import type { BrushSelectionConfig, TileSelection } from '@/types/BrushSelection';
import { isSingleTileSelection } from '@/types/BrushSelection';
import { ChevronDown } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

const tileSetStore = useTileSetStore();
const editorStore = useEditorStore();
const showModal = ref(false);
const isDropdownOpen = ref(false);
const activeTilesetContainer = ref<HTMLElement | null>(null);

// Use the tile selection composable
const { currentSelection, isDragging, calculateTileCoordinates, startDragSelection, continueDragSelection, completeDragSelection, clearSelection } =
    useTileSelection();

const mostUsedTileSetUuid = computed(() => {
    const usage = editorStore.mapMetadata.tilesetUsage;
    if (!usage || Object.keys(usage).length === 0) return null;
    const keys = Object.keys(usage);
    if (keys.length === 0) return null;
    return keys.reduce((max, uuid) => (usage[uuid] > usage[max] ? uuid : max), keys[0]);
});

onMounted(() => {
    if (mostUsedTileSetUuid.value) {
        tileSetStore.activateTileSet(mostUsedTileSetUuid.value);
    }
});

if (tileSetStore.tileSets.length === 0) {
    tileSetStore.loadTileSets();
}

function toggleDropdown() {
    isDropdownOpen.value = !isDropdownOpen.value;
}

function selectTileSet(uuid: string) {
    tileSetStore.activateTileSet(uuid);
    isDropdownOpen.value = false;
    // Clear selection when switching tilesets
    clearSelectionAndBrush();
}

function clearSelectionAndBrush() {
    clearSelection();
    editorStore.clearBrushSelection();
}

function addTileSet(url: string) {
    console.log('add', url);
    showModal.value = false;
}

function createBrushSelectionConfig(selection: TileSelection): BrushSelectionConfig {
    if (!tileSetStore.activeTileSet?.imageUrl) {
        throw new Error('No active tileset or image URL');
    }

    return {
        tileX: selection.tileX,
        tileY: selection.tileY,
        brushWidth: selection.width,
        brushHeight: selection.height,
        tilesetImageUrl: tileSetStore.activeTileSet.imageUrl,
        tilesetUuid: tileSetStore.activeTileSet.uuid,
    };
}

function updateBrushFromSelection(selection: TileSelection) {
    try {
        const config = createBrushSelectionConfig(selection);
        editorStore.setBrushSelection(config);
    } catch (error) {
        console.error('Failed to update brush selection:', error);
    }
}

function handleMouseDown(event: MouseEvent) {
    if (!tileSetStore.activeTileSet || !activeTilesetContainer.value) return;

    const coordinates = calculateTileCoordinates(
        event,
        activeTilesetContainer.value,
        tileSetStore.activeTileSet.tileWidth || 32,
        tileSetStore.activeTileSet.tileHeight || 32,
    );

    if (!coordinates) return;

    startDragSelection(coordinates.tileX, coordinates.tileY, tileSetStore.activeTileSet.tileWidth || 32, tileSetStore.activeTileSet.tileHeight || 32);
}

function handleMouseMove(event: MouseEvent) {
    if (!tileSetStore.activeTileSet || !activeTilesetContainer.value) return;

    const coordinates = calculateTileCoordinates(
        event,
        activeTilesetContainer.value,
        tileSetStore.activeTileSet.tileWidth || 32,
        tileSetStore.activeTileSet.tileHeight || 32,
    );

    if (!coordinates) return;

    continueDragSelection(
        coordinates.tileX,
        coordinates.tileY,
        tileSetStore.activeTileSet.tileWidth || 32,
        tileSetStore.activeTileSet.tileHeight || 32,
    );
}

function handleMouseUp(event: MouseEvent) {
    if (!tileSetStore.activeTileSet || !activeTilesetContainer.value) return;

    const coordinates = calculateTileCoordinates(
        event,
        activeTilesetContainer.value,
        tileSetStore.activeTileSet.tileWidth || 32,
        tileSetStore.activeTileSet.tileHeight || 32,
    );

    if (!coordinates) return;

    const selection = completeDragSelection(
        coordinates.tileX,
        coordinates.tileY,
        tileSetStore.activeTileSet.tileWidth || 32,
        tileSetStore.activeTileSet.tileHeight || 32,
    );

    if (selection) {
        updateBrushFromSelection(selection);
    }
}

function handleMouseLeave() {
    if (!isDragging.value || !tileSetStore.activeTileSet) return;

    // Complete the current selection when mouse leaves
    if (currentSelection.value) {
        updateBrushFromSelection(currentSelection.value);
    }

    // Stop dragging without clearing the selection
    isDragging.value = false;
}
</script>

<template>
    <div class="flex h-full max-h-full flex-col rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <!-- Header -->
        <div class="border-b border-gray-200 p-4 dark:border-gray-700">
            <h3 class="text-lg font-semibold">Tilesets</h3>
        </div>

        <!-- Body -->
        <div class="flex flex-1 flex-col gap-4 overflow-hidden p-4">
            <!-- Controls -->
            <div class="flex gap-2">
                <!-- Tileset Dropdown -->
                <div class="relative min-w-[200px]">
                    <button
                        type="button"
                        id="tileSetMenuButton"
                        class="flex w-full items-center justify-between gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700"
                        @click="toggleDropdown"
                    >
                        {{ tileSetStore.activeTileSet?.name || 'None' }}
                        <ChevronDown class="h-4 w-4 shrink-0" />
                    </button>
                    <ul
                        class="absolute top-full left-0 z-10 mt-1 min-w-[200px] rounded-lg border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-700 dark:bg-gray-800"
                        v-show="isDropdownOpen"
                    >
                        <li v-for="tileSet in tileSetStore.tileSets" :key="tileSet.uuid">
                            <button
                                @click="selectTileSet(tileSet.uuid)"
                                class="w-full truncate px-4 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700"
                            >
                                {{ tileSet.name }}
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Tileset Preview -->
            <div class="flex max-h-96 min-h-0 flex-1 overflow-auto border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
                <div
                    v-if="tileSetStore.activeTileSet"
                    ref="activeTilesetContainer"
                    :style="{
                        width: tileSetStore.activeTileSet.imageWidth + 'px',
                        height: tileSetStore.activeTileSet.imageHeight + 'px',
                        backgroundImage: `url('${tileSetStore.activeTileSet.imageUrl}')`,
                        backgroundRepeat: 'no-repeat',
                        backgroundSize: 'contain',
                    }"
                    class="relative min-w-full grow cursor-pointer bg-left"
                    @mousedown="handleMouseDown"
                    @mousemove="handleMouseMove"
                    @mouseup="handleMouseUp"
                    @mouseleave="handleMouseLeave"
                >
                    <!-- Single tile selection overlay -->
                    <div
                        v-if="currentSelection && isSingleTileSelection(currentSelection)"
                        class="pointer-events-none absolute bg-black/30 shadow-[inset_0_0_0_1px_rgba(0,0,0,1)]"
                        :style="{
                            left: currentSelection.x + 'px',
                            top: currentSelection.y + 'px',
                            width: currentSelection.width + 'px',
                            height: currentSelection.height + 'px',
                        }"
                    ></div>

                    <!-- Multi-tile selection overlay -->
                    <div
                        v-if="currentSelection && !isSingleTileSelection(currentSelection)"
                        class="pointer-events-none absolute bg-blue-500/30 shadow-[inset_0_0_0_2px_rgba(59,130,246,1)]"
                        :style="{
                            left: currentSelection.x + 'px',
                            top: currentSelection.y + 'px',
                            width: currentSelection.width + 'px',
                            height: currentSelection.height + 'px',
                        }"
                    ></div>
                </div>
            </div>
        </div>
    </div>
</template>

<style lang="scss" scoped>
.tile-set-wrapper {
    position: relative;

    height: 250px;
    overflow: scroll;

    background-color: #fff;

    &__selection {
        position: absolute;
        background-color: rgba(0, 0, 0, 0.3);
        background-image: none !important;
        pointer-events: none;
        box-shadow: inset 0px 0px 0px 1px #000;
    }
}
</style>
