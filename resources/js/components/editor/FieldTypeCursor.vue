<script setup lang="ts">
import { useEditorStore } from '@/stores/editorStore';
import type { CursorState } from '@/types/CursorState';
import { computed, inject } from 'vue';

const store = useEditorStore();

// Inject cursor state from parent
const cursorState = inject<CursorState>('cursorState');

if (!cursorState) {
    throw new Error('FieldTypeCursor must be used within a component that provides cursorState');
}

const { showCursor, cursorStyle } = cursorState;

// Get the selected field type
const selectedFieldType = computed(() => {
    const fieldTypeId = store.getSelectedFieldTypeId();
    if (!fieldTypeId) return null;
    return store.fieldTypes.find((ft) => ft.id === fieldTypeId);
});

// Cursor style with field type preview
const fieldTypeStyle = computed(() => {
    if (!selectedFieldType.value) return {};

    return {
        width: cursorState.mapTileWidth.value + 'px',
        height: cursorState.mapTileHeight.value + 'px',
        backgroundColor: selectedFieldType.value.color,
        display: 'flex',
        opacity: 0.8,
    };
});
</script>

<template>
    <div
        v-show="showCursor && selectedFieldType"
        class="pointer-events-none absolute opacity-50 transition-opacity duration-150"
        :style="cursorStyle"
    >
        <div :style="fieldTypeStyle"></div>
    </div>
</template>
