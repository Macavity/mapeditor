<script setup lang="ts">
import type { CursorState } from '@/types/CursorState';
import { inject } from 'vue';

// Inject shared cursor state from CanvasLayers (via ToolCursor)
const cursorState = inject<CursorState>('cursorState');

if (!cursorState) {
    throw new Error('EraseCursor must be used within a component that provides cursorState');
}

const { showCursor, cursorStyle, mapTileWidth, mapTileHeight } = cursorState;
</script>

<template>
    <!-- Simple transparent red square -->
    <div v-show="showCursor" class="pointer-events-none absolute opacity-25 transition-opacity duration-150" :style="cursorStyle">
        <div
            class="bg-red-500"
            :style="{
                width: mapTileWidth + 'px',
                height: mapTileHeight + 'px',
            }"
        ></div>
    </div>
</template>
