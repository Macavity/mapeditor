<template>
    <div class="flex h-full flex-col gap-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div v-if="!isEditingName" class="flex items-center gap-2">
                    <h1 class="text-2xl font-semibold">{{ map.name }}</h1>
                    <button
                        @click="startEditName"
                        class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                        title="Edit map name"
                    >
                        <Edit class="h-4 w-4" />
                    </button>
                </div>
                <div v-else class="flex items-center gap-2">
                    <input
                        ref="nameInput"
                        v-model="editingName"
                        type="text"
                        class="border-b-2 border-blue-500 bg-transparent text-2xl font-semibold focus:border-blue-600 focus:outline-none dark:text-white"
                        @keyup.enter="saveMapName"
                        @keyup.esc="cancelEdit"
                        @blur="saveMapName"
                    />
                </div>
            </div>
            <div class="flex items-center gap-4">
                <SaveStatus />
                <Link :href="`/maps/${map.uuid}/test`" class="btn btn-secondary flex items-center gap-2">
                    <Play class="h-4 w-4" />
                    Test Map
                </Link>
            </div>
        </div>

        <!-- Map Information -->
        <div class="text-sm text-gray-600 dark:text-gray-400">
            <div class="flex gap-4">
                <span>Size: {{ map.width }}×{{ map.height }}</span>
                <span>Tile: {{ map.tileWidth }}×{{ map.tileHeight }}</span>
                <span>Author: {{ map.externalCreator || map.creatorName || 'Unknown' }}</span>
            </div>
        </div>

        <div class="mb-4">
            <EditorToolbar />
        </div>

        <div class="flex flex-1 gap-4">
            <!-- Left Sidebar -->
            <aside class="flex min-h-0 w-80 shrink-0 flex-col gap-4 transition-all duration-200">
                <SidebarLayerControl />
            </aside>

            <!-- Main Canvas -->
            <section class="border-sidebar-border/70 dark:border-sidebar-border relative min-h-0 max-w-[calc(100%-40rem)] flex-1 overflow-auto">
                <Canvas />
            </section>

            <!-- Right Sidebar -->
            <aside class="flex min-h-0 w-80 shrink-0 flex-col gap-4">
                <section v-if="false" class="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4">
                    <SidebarMiniMap />
                </section>
                <div class="min-h-0 flex-1">
                    <SidebarTileSetBox v-if="isTileLayer && !isObjectLayer" />
                    <SidebarObjectBox v-else-if="isObjectLayer" />
                    <SidebarFieldTypeBox v-else-if="isFieldTypeLayer" />
                </div>
            </aside>
        </div>
    </div>
</template>

<script setup lang="ts">
import Canvas from '@/pages/maps/partials/canvas/Canvas.vue';
import EditorToolbar from '@/pages/maps/partials/EditorToolbar.vue';
import SaveStatus from '@/pages/maps/partials/SaveStatus.vue';
import SidebarFieldTypeBox from '@/pages/maps/partials/SidebarFieldTypeBox.vue';
import SidebarLayerControl from '@/pages/maps/partials/SidebarLayerControl.vue';
import SidebarMiniMap from '@/pages/maps/partials/SidebarMiniMap.vue';
import SidebarObjectBox from '@/pages/maps/partials/SidebarObjectBox.vue';
import SidebarTileSetBox from '@/pages/maps/partials/SidebarTileSetBox.vue';
import { MapService } from '@/services/MapService';
import { useEditorStore } from '@/stores/editorStore';
import { useObjectTypeStore } from '@/stores/objectTypeStore';
import { MapLayerType } from '@/types/MapLayer';
import { Link } from '@inertiajs/vue3';
import { Edit, Play } from 'lucide-vue-next';
import { computed, nextTick, onMounted, reactive, ref } from 'vue';

const store = useEditorStore();
const objectTypeStore = useObjectTypeStore();

const map = reactive(store.mapMetadata);
const isEditingName = ref(false);
const editingName = ref('');
const nameInput = ref<HTMLInputElement>();

// Initialize object type store when editor mounts
onMounted(async () => {
    await objectTypeStore.initialize();
});

// Computed properties to determine which sidebar to show
const activeLayer = computed(() => {
    if (!store.activeLayer) return null;
    return store.layers.find((layer) => layer.uuid === store.activeLayer);
});

const isTileLayer = computed(() => {
    if (!activeLayer.value) return false;
    return [MapLayerType.Sky, MapLayerType.Floor, MapLayerType.Background].includes(activeLayer.value.type);
});

const isObjectLayer = computed(() => {
    return activeLayer.value?.type === MapLayerType.Object;
});

const isFieldTypeLayer = computed(() => {
    return activeLayer.value?.type === MapLayerType.FieldType;
});

// Map name editing functions
const startEditName = async () => {
    editingName.value = map.name || '';
    isEditingName.value = true;
    await nextTick();
    nameInput.value?.focus();
    nameInput.value?.select();
};

const saveMapName = async () => {
    if (editingName.value.trim() && editingName.value.trim() !== map.name) {
        try {
            // Update the map name in the store
            map.name = editingName.value.trim();
            // Use MapService to save the map name to the backend
            await MapService.updateMap(map.uuid!, { name: map.name });
        } catch (error) {
            console.error('Failed to save map name:', error);
            // Revert the name if save failed
            editingName.value = map.name || '';
        }
    }
    isEditingName.value = false;
};

const cancelEdit = () => {
    isEditingName.value = false;
    editingName.value = '';
};
</script>

<style lang="scss">
// Z-index variables for canvas layering
$zGrid: 100;
$zSelection: 99;
$zBackground: 1;
$zCanvas: 0;

#canvas {
    @apply relative block bg-gray-400;
    z-index: $zCanvas;

    #grid {
        $width: 32px;
        $height: 32px;

        @apply absolute inset-0 opacity-40;
        z-index: $zGrid;
        background-size: $width $height;
        background-image:
            repeating-linear-gradient(0deg, theme('colors.black'), theme('colors.black') 1px, transparent 1px, transparent $width),
            repeating-linear-gradient(-90deg, theme('colors.black'), theme('colors.black') 1px, transparent 1px, transparent $height);
    }

    .layer {
        @apply absolute top-0 left-0 opacity-100 transition-opacity duration-150 ease-in-out;

        &.layer-invisible {
            @apply opacity-0;
        }
    }
}
</style>
