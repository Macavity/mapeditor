<script setup lang="ts">
import { useEditorStore } from '@/stores/editorStore';

const store = useEditorStore();
</script>

<template>
    <!-- Grid overlay (when enabled) -->
    <div
        v-if="store.showGrid"
        class="pointer-events-none absolute inset-0 opacity-40"
        :style="{
            width: store.canvasWidth + 'px',
            height: store.canvasHeight + 'px',
            backgroundSize: store.mapMetadata.tileWidth + 'px ' + store.mapMetadata.tileHeight + 'px',
            zIndex: 1000,
            backgroundImage: `
                repeating-linear-gradient(0deg, black, black 1px, transparent 1px, transparent ${store.mapMetadata.tileWidth}px),
                repeating-linear-gradient(-90deg, black, black 1px, transparent 1px, transparent ${store.mapMetadata.tileHeight}px)
            `,
        }"
    ></div>
</template>
