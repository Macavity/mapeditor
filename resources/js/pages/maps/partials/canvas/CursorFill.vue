<script setup lang="ts">
import { useCanvasInteraction } from '@/composables/useCanvasInteraction';
import { useUnifiedFloodFill } from '@/composables/useUnifiedFloodFill';
import { useEditorStore } from '@/stores/editorStore';
import type { CursorState } from '@/types/CursorState';
import { isFieldTypeLayer, isTileLayer } from '@/types/MapLayer';
import { computed, inject, ref } from 'vue';

const store = useEditorStore();
const { calculateTilePosition } = useCanvasInteraction();
const { getConnectedTiles, canFillTiles, getTileForPosition, getConnectedFieldTypes, canFillFieldTypes } = useUnifiedFloodFill();

// Inject shared cursor state from CanvasLayers (via ToolCursor)
const cursorState = inject<CursorState>('cursorState');

if (!cursorState) {
    throw new Error('FillCursor must be used within a component that provides cursorState');
}

const { showCursor, mapTileWidth, mapTileHeight } = cursorState;

// Track mouse position and connected items for preview
const hoveredTileX = ref<number | null>(null);
const hoveredTileY = ref<number | null>(null);
const connectedItems = ref<{ x: number; y: number }[]>([]);
const canShowPreview = ref(false);

// Get the active layer to determine the layer type
const activeLayer = computed(() => {
    if (!store.activeLayer) return null;
    return store.layers.find((layer) => layer.uuid === store.activeLayer);
});

const isActiveLayerTile = computed(() => {
    return activeLayer.value && isTileLayer(activeLayer.value);
});

const isActiveLayerFieldType = computed(() => {
    return activeLayer.value && isFieldTypeLayer(activeLayer.value);
});

// Update preview when mouse moves
function updatePreview(event: MouseEvent) {
    if (!event || !cursorState) {
        hoveredTileX.value = null;
        hoveredTileY.value = null;
        connectedItems.value = [];
        canShowPreview.value = false;
        return;
    }

    const position = calculateTilePosition(event);
    if (!position) {
        hoveredTileX.value = null;
        hoveredTileY.value = null;
        connectedItems.value = [];
        canShowPreview.value = false;
        return;
    }

    hoveredTileX.value = position.tileX;
    hoveredTileY.value = position.tileY;

    // Check if we can fill at this position based on layer type
    if (isActiveLayerTile.value) {
        canShowPreview.value = canFillTiles(position.tileX, position.tileY);
        if (canShowPreview.value) {
            connectedItems.value = getConnectedTiles(position.tileX, position.tileY);
        } else {
            connectedItems.value = [];
        }
    } else if (isActiveLayerFieldType.value) {
        canShowPreview.value = canFillFieldTypes(position.tileX, position.tileY);
        if (canShowPreview.value) {
            connectedItems.value = getConnectedFieldTypes(position.tileX, position.tileY);
        } else {
            connectedItems.value = [];
        }
    } else {
        canShowPreview.value = false;
        connectedItems.value = [];
    }
}

// Calculate the correct tile style for each position in the pattern
function getTileStyle(tileX: number, tileY: number) {
    const tileInfo = getTileForPosition(tileX, tileY, connectedItems.value);
    if (!tileInfo || !store.brushSelection.backgroundImage) {
        return {
            width: mapTileWidth.value + 'px',
            height: mapTileHeight.value + 'px',
            background: 'rgba(59, 130, 246, 0.3)',
        };
    }

    // Calculate background position for this specific tile in the pattern
    const backgroundPositionX = -tileInfo.tileX * store.mapMetadata.tileWidth;
    const backgroundPositionY = -tileInfo.tileY * store.mapMetadata.tileHeight;

    return {
        width: mapTileWidth.value + 'px',
        height: mapTileHeight.value + 'px',
        background: `url('${store.brushSelection.backgroundImage}') no-repeat`,
        backgroundPosition: `${backgroundPositionX}px ${backgroundPositionY}px`,
        backgroundSize: 'auto',
    };
}

// Get the field type style for preview
function getFieldTypeStyle() {
    const selectedFieldTypeId = store.getSelectedFieldTypeId();
    if (selectedFieldTypeId === null) return {};

    const selectedFieldType = store.fieldTypes.find((ft) => ft.id === selectedFieldTypeId);
    if (!selectedFieldType) return {};

    return {
        width: mapTileWidth.value + 'px',
        height: mapTileHeight.value + 'px',
        backgroundColor: selectedFieldType.color,
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        fontSize: '10px',
        fontWeight: 'bold',
        color: '#fff',
        textShadow: '1px 1px 2px rgba(0, 0, 0, 0.8)',
    };
}

// Get the appropriate style based on layer type
function getItemStyle(itemX: number, itemY: number) {
    if (isActiveLayerTile.value) {
        return getTileStyle(itemX, itemY);
    } else if (isActiveLayerFieldType.value) {
        return getFieldTypeStyle();
    }
    return {};
}

// Expose the update function for parent component to call
defineExpose({
    updatePreview,
});
</script>

<template>
    <!-- Preview items for bucket fill -->
    <div v-if="showCursor && canShowPreview && connectedItems.length > 0" class="pointer-events-none absolute inset-0 z-40">
        <div
            v-for="item in connectedItems"
            :key="`${item?.x ?? 0}-${item?.y ?? 0}`"
            class="absolute opacity-60 transition-opacity duration-150"
            :style="{
                left: (item?.x ?? 0) * mapTileWidth + 'px',
                top: (item?.y ?? 0) * mapTileHeight + 'px',
                ...getItemStyle(item?.x ?? 0, item?.y ?? 0),
            }"
        >
            <!-- Show field type name for field type layers -->
            <span v-if="isActiveLayerFieldType" class="text-xs font-bold text-white"> </span>
        </div>
    </div>
</template>
