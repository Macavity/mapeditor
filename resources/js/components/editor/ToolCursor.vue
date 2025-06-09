<script setup lang="ts">
import { useEditorStore } from '@/stores/editorStore';
import type { CursorState } from '@/types/CursorState';
import { EditorTool } from '@/types/EditorTool';
import { computed, defineAsyncComponent, inject, provide } from 'vue';

const store = useEditorStore();

// Inject shared cursor state from CanvasLayers
const cursorState = inject<CursorState>('cursorState');

// Lazy load tool components only when needed
const BrushCursor = defineAsyncComponent(() => import('./BrushCursor.vue'));
const EraseCursor = defineAsyncComponent(() => import('./EraseCursor.vue'));

// Only instantiate the component for the active tool
const activeToolComponent = computed(() => {
    switch (store.activeTool) {
        case EditorTool.DRAW:
            // Only show brush cursor if we have a valid brush selection
            return store.brushSelection.tilesetUuid && store.brushSelection.backgroundImage ? BrushCursor : null;
        case EditorTool.ERASE:
            return EraseCursor;
        case EditorTool.FILL:
            // Future: return FillCursor when implemented
            return null;
        default:
            return null;
    }
});

// Provide cursor state to child cursor components
if (cursorState) {
    provide('cursorState', cursorState);
}
</script>

<template>
    <!-- Only render the active tool with shared cursor state -->
    <component :is="activeToolComponent" v-if="activeToolComponent" />
</template>
