<script setup lang="ts">
import { useEditorStore } from '@/stores/editorStore';
import { onMounted, ref } from 'vue';

const store = useEditorStore();

const brushSelection = {
    width: 32,
    height: 32,
    backgroundImage: null,
};

const showBrush = ref(false);
const brushPosition = ref({ x: 0, y: 0 });

onMounted(() => {
    for (const layer of store.layers) {
        console.log('Draw Layer', layer.uuid);
    }
});

const onMouseEnter = () => {
    showBrush.value = true;
};

const onMouseLeave = () => {
    showBrush.value = false;
};

const onMouseMove = (event: MouseEvent) => {
    if (!store.map || !showBrush.value) return;

    const target = event.currentTarget as HTMLElement;
    const rect = target.getBoundingClientRect();

    // Calculate mouse position relative to canvas
    const mouseX = event.clientX - rect.left;
    const mouseY = event.clientY - rect.top;

    // Get tile dimensions from the map
    const tileWidth = store.map.tile_width;
    const tileHeight = store.map.tile_height;

    // Calculate tile coordinates (snap to grid)
    const tileX = Math.floor(mouseX / tileWidth);
    const tileY = Math.floor(mouseY / tileHeight);

    // Convert back to pixel coordinates (snapped to tile grid)
    const pixelX = tileX * tileWidth;
    const pixelY = tileY * tileHeight;

    // Update brush position
    brushPosition.value = { x: pixelX, y: pixelY };
};
</script>

<template>
    <div
        @mousemove="onMouseMove"
        @mouseenter="onMouseEnter"
        @mouseleave="onMouseLeave"
        class="relative"
        :style="{
            width: store.canvasWidth + 'px',
            height: store.canvasHeight + 'px',
        }"
    >
        <!-- Selection/Brush cursor -->
        <div
            v-show="showBrush"
            class="pointer-events-none absolute opacity-50 transition-opacity duration-150"
            :style="{
                left: brushPosition.x + 'px',
                top: brushPosition.y + 'px',
                zIndex: 99,
            }"
        >
            <div
                id="brush"
                class="border border-black bg-blue-200 dark:bg-blue-400"
                :style="{
                    width: (store.map?.tile_width || 32) + 'px',
                    height: (store.map?.tile_height || 32) + 'px',
                    background: brushSelection.backgroundImage ? 'url(\'' + brushSelection.backgroundImage + '\') no-repeat' : undefined,
                }"
            ></div>
        </div>

        <!-- Tilemap layers container -->
        <div class="relative">
            <canvas
                v-for="layer in store.layers"
                :key="layer.id"
                class="absolute transition-opacity duration-150 ease-in-out"
                :class="{
                    'opacity-100': layer.visible,
                    'opacity-0': !layer.visible,
                }"
                :style="{
                    'z-index': layer.z,
                    width: store.canvasWidth + 'px',
                    height: store.canvasHeight + 'px',
                }"
                :width="store.canvasWidth"
                :height="store.canvasHeight"
            >
            </canvas>
        </div>

        <!-- Grid overlay (when enabled) -->
        <div
            v-if="store.showGrid"
            class="pointer-events-none absolute inset-0 opacity-40"
            :style="{
                width: store.canvasWidth + 'px',
                height: store.canvasHeight + 'px',
                backgroundSize: (store.map?.tile_width || 32) + 'px ' + (store.map?.tile_height || 32) + 'px',
                backgroundImage: `
                    repeating-linear-gradient(0deg, black, black 1px, transparent 1px, transparent ${store.map?.tile_width || 32}px),
                    repeating-linear-gradient(-90deg, black, black 1px, transparent 1px, transparent ${store.map?.tile_height || 32}px)
                `,
            }"
        ></div>
    </div>
</template>

<style lang="scss" scoped>
.selection {
    z-index: 99;
    box-shadow: inset 0px 0px 0px 1px theme('colors.black');
}
</style>
