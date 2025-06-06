<script setup lang="ts">
import { useEditorStore } from '@/stores/editorStore';
import { nextTick, ref } from 'vue';
import BrushCursor from './BrushCursor.vue';
import GridOverlay from './GridOverlay.vue';
import TileCanvas from './TileCanvas.vue';

const store = useEditorStore();
const brushCursorRef = ref<InstanceType<typeof BrushCursor> | null>(null);
const tileCanvasRefs = ref<{ [key: string]: InstanceType<typeof TileCanvas> }>({});

const setTileCanvasRef = (el: InstanceType<typeof TileCanvas> | null, layerUuid: string) => {
    if (el) {
        tileCanvasRefs.value[layerUuid] = el;
    }
};

const onCanvasClick = (event: MouseEvent) => {
    // Only place tile if we have a brush selection
    if (!store.brushSelection.tilesetUuid || !store.brushSelection.backgroundImage) {
        return;
    }

    const target = event.currentTarget as HTMLElement;
    const rect = target.getBoundingClientRect();

    // Calculate mouse position relative to canvas
    const mouseX = event.clientX - rect.left;
    const mouseY = event.clientY - rect.top;

    // Get tile dimensions from the map
    const tileWidth = store.mapMetadata.tileWidth;
    const tileHeight = store.mapMetadata.tileHeight;

    // Calculate tile coordinates (snap to grid)
    const tileX = Math.floor(mouseX / tileWidth);
    const tileY = Math.floor(mouseY / tileHeight);

    // Place the tile
    store.placeTile(tileX, tileY);

    // Ensure the active layer is re-rendered immediately
    if (store.activeLayer && tileCanvasRefs.value[store.activeLayer]) {
        nextTick(() => {
            tileCanvasRefs.value[store.activeLayer!].renderLayer();
        });
    }
};
</script>

<template>
    <div
        @mousemove="brushCursorRef?.onMouseMove"
        @mouseenter="brushCursorRef?.onMouseEnter"
        @mouseleave="brushCursorRef?.onMouseLeave"
        @click="onCanvasClick"
        class="border-opacity-50 relative border"
        :style="{
            width: store.canvasWidth + 'px',
            height: store.canvasHeight + 'px',
        }"
    >
        <!-- Brush cursor -->
        <BrushCursor ref="brushCursorRef" />

        <!-- Tilemap layers container -->
        <div class="relative">
            <TileCanvas
                v-for="layer in store.layersSortedByZ"
                :key="layer.uuid"
                :ref="(el) => setTileCanvasRef(el as InstanceType<typeof TileCanvas>, layer.uuid)"
                :layer="layer"
            />
        </div>

        <!-- Grid overlay -->
        <GridOverlay />
    </div>
</template>

<style lang="scss" scoped>
.selection {
    z-index: 99;
    box-shadow: inset 0px 0px 0px 1px theme('colors.black');
}
</style>
