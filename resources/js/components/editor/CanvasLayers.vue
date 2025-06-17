<script setup lang="ts">
import { useCanvasCursor } from '@/composables/useCanvasCursor';
import { useCanvasInteraction } from '@/composables/useCanvasInteraction';
import { useEditorStore } from '@/stores/editorStore';
import type { CursorState } from '@/types/CursorState';
import { EditorTool } from '@/types/EditorTool';
import { computed, nextTick, provide, ref } from 'vue';
import GridOverlay from './GridOverlay.vue';
import TileCanvas from './TileCanvas.vue';
import ToolCursor from './ToolCursor.vue';

const store = useEditorStore();
const { handleCanvasClick } = useCanvasInteraction();
const tileCanvasRefs = ref<{ [key: string]: InstanceType<typeof TileCanvas> }>({});
const toolCursorRef = ref<InstanceType<typeof ToolCursor> | null>(null);

// Feedback state for empty tile clicks
const showEmptyTileMessage = ref(false);
const emptyTileMessageTimeout = ref<NodeJS.Timeout | null>(null);

// Centralized cursor state management
const mapTileWidth = computed(() => store.mapMetadata.tileWidth);
const mapTileHeight = computed(() => store.mapMetadata.tileHeight);

// Initialize cursor composable - this will be the single source of truth
const { showCursor, cursorStyle, show, hide, updatePosition } = useCanvasCursor(mapTileWidth, mapTileHeight);

// Provide cursor state to child components
const cursorState: CursorState = {
    showCursor,
    cursorStyle,
    mapTileWidth,
    mapTileHeight,
};

provide('cursorState', cursorState);

const setTileCanvasRef = (el: InstanceType<typeof TileCanvas> | null, layerUuid: string) => {
    if (el) {
        tileCanvasRefs.value[layerUuid] = el;
    }
};

const showEmptyTileFeedback = () => {
    showEmptyTileMessage.value = true;

    // Clear existing timeout
    if (emptyTileMessageTimeout.value) {
        clearTimeout(emptyTileMessageTimeout.value);
    }

    // Hide message after 2 seconds
    emptyTileMessageTimeout.value = setTimeout(() => {
        showEmptyTileMessage.value = false;
    }, 2000);
};

const onCanvasClick = (event: MouseEvent) => {
    const result = handleCanvasClick(event);

    if (result.success) {
        // Re-render the active layer immediately
        if (store.activeLayer && tileCanvasRefs.value[store.activeLayer]) {
            nextTick(() => {
                tileCanvasRefs.value[store.activeLayer!].renderLayer();
            });
        }

        // Show feedback for erase on empty tile
        if (result.action === 'erase' && result.tileExists === false) {
            showEmptyTileFeedback();
        }

        // Note: For fill action, result.tileExists indicates if any tiles were actually filled
        // No special feedback needed for fill as the visual result is immediate
    }
};

// Centralized mouse event handlers
const handleMouseMove = (event: MouseEvent) => {
    // Only handle mouse move for tools that need cursor tracking
    if (store.activeTool === EditorTool.DRAW || store.activeTool === EditorTool.ERASE || store.activeTool === EditorTool.FILL) {
        updatePosition(event);

        // Update fill preview when fill tool is active
        if (store.activeTool === EditorTool.FILL && toolCursorRef.value) {
            toolCursorRef.value.updateFillPreview(event);
        }
    }
};

const handleMouseEnter = () => {
    // Only show cursor for tools that need it
    if (store.activeTool === EditorTool.DRAW || store.activeTool === EditorTool.ERASE || store.activeTool === EditorTool.FILL) {
        show();
    }
};

const handleMouseLeave = () => {
    // Hide cursor for all tools
    hide();
};
</script>

<template>
    <div
        @mousemove="handleMouseMove"
        @mouseenter="handleMouseEnter"
        @mouseleave="handleMouseLeave"
        @click="onCanvasClick"
        class="border-opacity-50 relative border"
    >
        <!-- Restored tool cursor component with shared state -->
        <ToolCursor ref="toolCursorRef" />

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

        <!-- Empty tile feedback message -->
        <div
            v-if="showEmptyTileMessage"
            class="pointer-events-none absolute top-4 left-1/2 z-50 -translate-x-1/2 transform rounded-lg bg-yellow-100 px-3 py-2 text-sm text-yellow-800 shadow-lg transition-opacity duration-300 dark:bg-yellow-900 dark:text-yellow-200"
        >
            <span class="flex items-center gap-2">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                    <path
                        fill-rule="evenodd"
                        d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                        clip-rule="evenodd"
                    />
                </svg>
                No tile to erase
            </span>
        </div>
    </div>
</template>

<style lang="scss" scoped>
.selection {
    z-index: 99;
    box-shadow: inset 0px 0px 0px 1px theme('colors.black');
}
</style>
