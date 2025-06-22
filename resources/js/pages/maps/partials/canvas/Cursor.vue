<script setup lang="ts">
import { useEditorStore } from '@/stores/editorStore';
import type { CursorState } from '@/types/CursorState';
import { EditorTool } from '@/types/EditorTool';
import { isFieldTypeLayer, isTileLayer } from '@/types/MapLayer';
import { computed, defineAsyncComponent, inject, provide, ref } from 'vue';

const store = useEditorStore();

// Inject shared cursor state from CanvasLayers
const cursorState = inject<CursorState>('cursorState');

if (!cursorState) {
    throw new Error('ToolCursor: cursorState not provided, cursor functionality may not work properly');
}

// Lazy load tool components only when needed
const CursorBrush = defineAsyncComponent(() => import('./CursorBrush.vue'));
const CursorErase = defineAsyncComponent(() => import('./CursorErase.vue'));
const CursorFill = defineAsyncComponent(() => import('./CursorFill.vue'));
const CursorFieldType = defineAsyncComponent(() => import('./CursorFieldType.vue'));

// Reference to the active cursor component
const activeCursorRef = ref<any>(null);

// Get the active layer
const activeLayer = computed(() => {
    if (!store.activeLayer) return null;
    return store.layers.find((layer) => layer.uuid === store.activeLayer);
});

// Only instantiate the component for the active tool and layer type
const activeToolComponent = computed(() => {
    const layer = activeLayer.value;

    if (!layer) return null;

    switch (store.activeTool) {
        case EditorTool.DRAW:
            if (isTileLayer(layer)) {
                // Show brush cursor for tile layers if we have a valid brush selection
                return store.brushSelection.tilesetUuid && store.brushSelection.backgroundImage ? CursorBrush : null;
            } else if (isFieldTypeLayer(layer)) {
                // Show field type cursor for field type layers if we have a selected field type
                return store.getSelectedFieldTypeId() !== null ? CursorFieldType : null;
            }
            return null;
        case EditorTool.ERASE:
            return CursorErase;
        case EditorTool.FILL:
            if (isTileLayer(layer)) {
                // Show fill cursor for tile layers if we have a valid brush selection
                return store.brushSelection.tilesetUuid && store.brushSelection.backgroundImage ? CursorFill : null;
            } else if (isFieldTypeLayer(layer)) {
                // Show fill cursor for field type layers if we have a selected field type
                return store.getSelectedFieldTypeId() !== null ? CursorFill : null;
            }
            return null;
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
