<template>
    <div class="flex h-full flex-col gap-4">
        <h1 class="text-2xl font-semibold">{{ map.name }}</h1>

        <div class="mb-4">
            <EditorToolbar />
        </div>

        <div class="flex flex-1 gap-4">
            <!-- Left Sidebar -->
            <aside class="flex w-80 shrink-0 flex-col gap-4 transition-all duration-200">
                <section class="border-sidebar-border/70 dark:border-sidebar-border flex-1 rounded-xl border p-4">
                    <EditorLayers />
                </section>
                <section class="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4" v-show="store.showProperties">
                    <EditorMapProperties v-if="store.mapMetadata" />
                </section>
            </aside>

            <!-- Main Canvas -->
            <section class="border-sidebar-border/70 dark:border-sidebar-border relative max-w-[calc(100%-40rem)] flex-1 overflow-auto">
                <CanvasLayers />
            </section>

            <!-- Right Sidebar -->
            <aside class="flex w-80 shrink-0 flex-col">
                <section v-if="false" class="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4">
                    <EditorMiniMap />
                </section>
                <TileSetBox />
            </aside>
        </div>
    </div>
</template>

<script setup lang="ts">
import CanvasLayers from '@/components/editor/CanvasLayers.vue';
import EditorLayers from '@/components/editor/EditorLayers.vue';
import EditorMapProperties from '@/components/editor/EditorMapProperties.vue';
import EditorMiniMap from '@/components/editor/EditorMiniMap.vue';
import EditorToolbar from '@/components/editor/EditorToolbar.vue';
import TileSetBox from '@/components/editor/TileSetBox.vue';
import { useEditorStore } from '@/stores/editorStore';
import { reactive } from 'vue';

const store = useEditorStore();

const map = reactive(store.mapMetadata);
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
