<script setup lang="ts">
import { useEditorStore } from '@/stores/editorStore';
import type { CursorState } from '@/types/CursorState';
import { computed, inject } from 'vue';

const store = useEditorStore();

// Inject shared cursor state from CanvasLayers (via ToolCursor)
const cursorState = inject<CursorState>('cursorState');

if (!cursorState) {
    throw new Error('BrushCursor must be used within a component that provides cursorState');
}

const { showCursor, cursorStyle } = cursorState;

const brushStyle = computed(() => ({
    width: store.brushSelection.width + 'px',
    height: store.brushSelection.height + 'px',
    background: store.brushSelection.backgroundImage ? `url('${store.brushSelection.backgroundImage}') no-repeat` : undefined,
    backgroundPosition: store.brushSelection.backgroundImage ? store.brushSelection.backgroundPosition : undefined,
}));
</script>

<template>
    <!-- Pure display component using shared cursor state -->
    <div v-show="showCursor" class="pointer-events-none absolute opacity-50 transition-opacity duration-150" :style="cursorStyle">
        <div id="brush" class="border border-black bg-blue-200 dark:bg-blue-400" :style="brushStyle"></div>
    </div>
</template>
