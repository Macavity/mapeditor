<script setup lang="ts">
import { useBrushCursor } from '@/composables/useBrushCursor';
import { useEditorStore } from '@/stores/editorStore';
import { computed } from 'vue';

const store = useEditorStore();

const mapTileWidth = computed(() => store.mapMetadata.tileWidth);
const mapTileHeight = computed(() => store.mapMetadata.tileHeight);

const { showBrush, cursorStyle, showCursor, hideCursor, updateCursorPosition } = useBrushCursor(mapTileWidth, mapTileHeight);

const brushStyle = computed(() => ({
    width: store.brushSelection.width + 'px',
    height: store.brushSelection.height + 'px',
    background: store.brushSelection.backgroundImage ? `url('${store.brushSelection.backgroundImage}') no-repeat` : undefined,
    backgroundPosition: store.brushSelection.backgroundImage ? store.brushSelection.backgroundPosition : undefined,
}));

defineExpose({
    onMouseEnter: showCursor,
    onMouseLeave: hideCursor,
    onMouseMove: updateCursorPosition,
});
</script>

<template>
    <div v-show="showBrush" class="pointer-events-none absolute opacity-50 transition-opacity duration-150" :style="cursorStyle">
        <div id="brush" class="border border-black bg-blue-200 dark:bg-blue-400" :style="brushStyle"></div>
    </div>
</template>
