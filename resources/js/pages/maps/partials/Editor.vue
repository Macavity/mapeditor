<template>
    <div class="flex h-full flex-col gap-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold">{{ map.name }}</h1>
            <div class="flex items-center gap-4">
                <SaveStatus />
                <Link :href="`/maps/${map.uuid}/test`" class="btn btn-secondary flex items-center gap-2">
                    <Play class="h-4 w-4" />
                    Test Map
                </Link>
            </div>
        </div>

        <div class="mb-4">
            <EditorToolbar />
        </div>

        <div class="flex flex-1 gap-4">
            <!-- Left Sidebar -->
            <aside class="flex min-h-0 w-80 shrink-0 flex-col gap-4 transition-all duration-200">
                <SidebarLayerControl />
                <SidebarProperties v-if="store.showProperties" />
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
import SidebarProperties from '@/pages/maps/partials/SidebarProperties.vue';
import SidebarTileSetBox from '@/pages/maps/partials/SidebarTileSetBox.vue';
import { useEditorStore } from '@/stores/editorStore';
import { MapLayerType } from '@/types/MapLayer';
import { Link } from '@inertiajs/vue3';
import { Play } from 'lucide-vue-next';
import { computed, reactive } from 'vue';

const store = useEditorStore();

const map = reactive(store.mapMetadata);

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
