<script setup lang="ts">
import { useCanvasInteraction } from '@/composables/useCanvasInteraction';
import { useFloodFill } from '@/composables/useFloodFill';
import { useEditorStore } from '@/stores/editorStore';
import type { CursorState } from '@/types/CursorState';
import { inject, ref } from 'vue';

const store = useEditorStore();
const { calculateTilePosition } = useCanvasInteraction();
const { getConnectedTiles, canFill } = useFloodFill();

// Inject shared cursor state from CanvasLayers (via ToolCursor)
const cursorState = inject<CursorState>('cursorState');

if (!cursorState) {
    throw new Error('FillCursor must be used within a component that provides cursorState');
}

const { showCursor, mapTileWidth, mapTileHeight } = cursorState;

// Track mouse position and connected tiles for preview
const hoveredTileX = ref<number | null>(null);
const hoveredTileY = ref<number | null>(null);
const connectedTiles = ref<{ x: number; y: number }[]>([]);
const canShowPreview = ref(false);

// Update preview when mouse moves
function updatePreview(event: MouseEvent) {
    const position = calculateTilePosition(event);
    if (!position) {
        hoveredTileX.value = null;
        hoveredTileY.value = null;
        connectedTiles.value = [];
        canShowPreview.value = false;
        return;
    }

    hoveredTileX.value = position.tileX;
    hoveredTileY.value = position.tileY;

    // Check if we can fill at this position
    canShowPreview.value = canFill(position.tileX, position.tileY);

    if (canShowPreview.value) {
        // Get all connected tiles that would be filled
        connectedTiles.value = getConnectedTiles(position.tileX, position.tileY);
    } else {
        connectedTiles.value = [];
    }
}

// Expose the update function for parent component to call
defineExpose({
    updatePreview,
});
</script>

<template>
    <!-- Preview tiles for bucket fill -->
    <div v-if="showCursor && canShowPreview && connectedTiles.length > 0" class="pointer-events-none absolute inset-0 z-40 border-2 border-blue-400">
        <div
            v-for="tile in connectedTiles"
            :key="`${tile.x}-${tile.y}`"
            class="absolute opacity-60 transition-opacity duration-150"
            :style="{
                left: tile.x * mapTileWidth + 'px',
                top: tile.y * mapTileHeight + 'px',
                width: mapTileWidth + 'px',
                height: mapTileHeight + 'px',
                background: store.brushSelection.backgroundImage
                    ? `url('${store.brushSelection.backgroundImage}') no-repeat`
                    : 'rgba(59, 130, 246, 0.3)',
                backgroundPosition: store.brushSelection.backgroundImage ? store.brushSelection.backgroundPosition : undefined,
                backgroundSize: store.brushSelection.backgroundImage ? 'auto' : '100% 100%',
            }"
        ></div>
    </div>
</template>
