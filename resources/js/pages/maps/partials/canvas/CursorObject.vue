<script setup lang="ts">
import { useObjectTypeStore } from '@/stores/objectTypeStore';
import type { CursorState } from '@/types/CursorState';
import { computed, inject } from 'vue';

const objectTypeStore = useObjectTypeStore();

// Inject shared cursor state
const cursorState = inject<CursorState>('cursorState');

if (!cursorState) {
    throw new Error('CursorObject must be used within a component that provides cursorState');
}

const { showCursor, cursorStyle, mapTileWidth, mapTileHeight } = cursorState;

// Get the active object type for styling
const activeObjectType = objectTypeStore.activeObjectType;

// Calculate the cursor style with object color
const objectCursorStyle = computed(() => {
    if (!activeObjectType) return cursorStyle.value;

    return {
        ...cursorStyle.value,
        width: mapTileWidth.value + 'px',
        height: mapTileHeight.value + 'px',
        backgroundColor: activeObjectType.color,
        opacity: 0.5,
        border: `2px solid ${activeObjectType.color}`,
        borderRadius: '4px',
    };
});
</script>

<template>
    <div v-if="showCursor && activeObjectType" class="pointer-events-none absolute z-40" :style="objectCursorStyle">
        <!-- Optional: Add a small indicator to show it's an object -->
        <div class="absolute -top-1 -right-1 flex h-3 w-3 items-center justify-center rounded-full border border-gray-300 bg-white">
            <span class="text-xs font-bold text-gray-700">{{ activeObjectType.name.charAt(0).toUpperCase() }}</span>
        </div>
    </div>
</template>
