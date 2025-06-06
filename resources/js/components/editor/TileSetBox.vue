<script setup lang="ts">
import AddTileSetModal from '@/components/editor/AddTileSetModal.vue';
import { useTileSetStore } from '@/stores/tileSetStore';
import { ChevronDown } from 'lucide-vue-next';
import { ref } from 'vue';

const tileSetStore = useTileSetStore();
const showModal = ref(false);
const isDropdownOpen = ref(false);
const activeTilesetContainer = ref<HTMLElement | null>(null);

// Selected tile state
const selectedTile = ref<{ x: number; y: number; tileX: number; tileY: number } | null>(null);

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
    selectedTile.value = null;
}

function addTileSet(url: string) {
    console.log('add', url);
    showModal.value = false;
    // TileSetFactory.create();
    // tileSetStore.addTileSet();
}

function handleTilesetClick(event: MouseEvent) {
    if (!tileSetStore.activeTileSet || !activeTilesetContainer.value) {
        return;
    }

    const rect = activeTilesetContainer.value.getBoundingClientRect();
    const x = event.clientX - rect.left;
    const y = event.clientY - rect.top;

    // Calculate which tile was clicked based on tile dimensions
    const tileWidth = tileSetStore.activeTileSet.tileWidth || 32;
    const tileHeight = tileSetStore.activeTileSet.tileHeight || 32;

    const tileX = Math.floor(x / tileWidth);
    const tileY = Math.floor(y / tileHeight);

    // Calculate the actual pixel position for the overlay
    const overlayX = tileX * tileWidth;
    const overlayY = tileY * tileHeight;

    selectedTile.value = {
        x: overlayX,
        y: overlayY,
        tileX,
        tileY,
    };
}
</script>

<template>
    <div class="flex h-full flex-col rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <AddTileSetModal v-if="showModal" :show="showModal" @close="showModal = false" @addTileSet="addTileSet" />

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

                <!-- Add Tileset Button -->
                <!-- <button
                    @click="showModal = true"
                    type="button"
                    class="border-primary text-primary hover:bg-primary flex items-center gap-2 rounded-lg border px-4 py-2 text-sm transition-colors hover:text-white"
                >
                    <Plus class="h-4 w-4" />
                    Add TileSet
                </button> -->
            </div>

            <!-- Tileset Preview -->
            <div class="flex min-h-[16rem] flex-1 overflow-y-auto border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
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
                    @click="handleTilesetClick"
                >
                    <!-- Selection overlay -->
                    <div
                        v-if="selectedTile"
                        class="pointer-events-none absolute bg-black/30 shadow-[inset_0_0_0_1px_rgba(0,0,0,1)]"
                        :style="{
                            left: selectedTile.x + 'px',
                            top: selectedTile.y + 'px',
                            width: (tileSetStore.activeTileSet.tileWidth || 32) + 'px',
                            height: (tileSetStore.activeTileSet.tileHeight || 32) + 'px',
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
