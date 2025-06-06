<script setup lang="ts">
import { useEditorStore } from '@/stores/editorStore';
import { ref } from 'vue';

const store = useEditorStore();
const showBrush = ref(false);
const brushPosition = ref({ x: 0, y: 0 });

const onMouseEnter = () => {
    showBrush.value = true;
};

const onMouseLeave = () => {
    showBrush.value = false;
};

const onMouseMove = (event: MouseEvent) => {
    if (!showBrush.value) return;

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

    // Convert back to pixel coordinates (snapped to tile grid)
    const pixelX = tileX * tileWidth;
    const pixelY = tileY * tileHeight;

    // Update brush position
    brushPosition.value = { x: pixelX, y: pixelY };
};

// Expose methods for parent component
defineExpose({
    onMouseEnter,
    onMouseLeave,
    onMouseMove,
});
</script>

<template>
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
                width: store.mapMetadata.tileWidth + 'px',
                height: store.mapMetadata.tileHeight + 'px',
                background: store.brushSelection.backgroundImage ? 'url(\'' + store.brushSelection.backgroundImage + '\') no-repeat' : undefined,
                backgroundPosition: store.brushSelection.backgroundImage ? store.brushSelection.backgroundPosition : undefined,
            }"
        ></div>
    </div>
</template>
