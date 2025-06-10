<script setup lang="ts">
import { useEditorStore } from '@/stores/editorStore';
import type { CursorState } from '@/types/CursorState';
import { EditorTool } from '@/types/EditorTool';
import { computed, defineAsyncComponent, inject, provide, ref } from 'vue';

const store = useEditorStore();

// Inject shared cursor state from CanvasLayers
const cursorState = inject<CursorState>('cursorState');

// Lazy load tool components only when needed
const BrushCursor = defineAsyncComponent(() => import('./BrushCursor.vue'));
const EraseCursor = defineAsyncComponent(() => import('./EraseCursor.vue'));
const FillCursor = defineAsyncComponent(() => import('./FillCursor.vue'));

// Reference to the active cursor component
const activeCursorRef = ref<any>(null);

// Only instantiate the component for the active tool
const activeToolComponent = computed(() => {
    switch (store.activeTool) {
        case EditorTool.DRAW:
            // Only show brush cursor if we have a valid brush selection
            return store.brushSelection.tilesetUuid && store.brushSelection.backgroundImage ? BrushCursor : null;
        case EditorTool.ERASE:
            return EraseCursor;
        case EditorTool.FILL:
            // Only show fill cursor if we have a valid brush selection
            return store.brushSelection.tilesetUuid && store.brushSelection.backgroundImage ? FillCursor : null;
        default:
            return null;
    }
});

// Provide cursor state to child cursor components
if (cursorState) {
    provide('cursorState', cursorState);
}

// Expose methods for parent component to call
function updateFillPreview(event: MouseEvent) {
    if (store.activeTool === EditorTool.FILL && activeCursorRef.value?.updatePreview) {
        activeCursorRef.value.updatePreview(event);
    }
}

defineExpose({
    updateFillPreview,
});
</script>

<template>
    <!-- Only render the active tool with shared cursor state -->
    <component :is="activeToolComponent" v-if="activeToolComponent" ref="activeCursorRef" />
</template>
