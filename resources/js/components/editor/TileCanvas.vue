<script setup lang="ts">
import { useEditorStore } from '@/stores/editorStore';
import { useTileSetStore } from '@/stores/tileSetStore';
import { isFieldTypeLayer, isTileLayer, type FieldTypeTile, type MapLayer, type Tile } from '@/types/MapLayer';
import { nextTick, onMounted, ref, watch } from 'vue';

interface Props {
    layer: MapLayer;
}

const props = defineProps<Props>();
const store = useEditorStore();
const tileSetStore = useTileSetStore();

const canvasRef = ref<HTMLCanvasElement | null>(null);

const tryRender = () => {
    if (store.loaded && props.layer.data?.length >= 0) {
        nextTick(() => {
            renderLayer();
        });
    }
};

// Initial render on mount
onMounted(tryRender);

// Watch for store becoming loaded
watch(() => store.loaded, tryRender);

// Watch for layer data changes and re-render
watch(() => props.layer.data, tryRender, { deep: true });

// Watch for layer property changes
watch(() => [props.layer.visible, props.layer.opacity], tryRender);

const renderLayer = async () => {
    const canvas = canvasRef.value;
    if (!canvas) {
        console.warn('Canvas not found for layer', props.layer.uuid);
        return;
    }

    const ctx = canvas.getContext('2d');
    if (!ctx) {
        console.error('Context not found for layer', props.layer.uuid);
        return;
    }

    // Clear the canvas
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    if (!props.layer.data || !Array.isArray(props.layer.data) || props.layer.data.length === 0) {
        return;
    }

    if (isTileLayer(props.layer)) {
        await renderTileLayer(ctx);
    } else if (isFieldTypeLayer(props.layer)) {
        await renderFieldTypeLayer(ctx);
    }
};

const renderTileLayer = async (ctx: CanvasRenderingContext2D) => {
    // Ensure tilesets are loaded first
    if (tileSetStore.tileSets.length === 0) {
        await tileSetStore.loadTileSets();
    }

    // Load all unique tilesets used in this layer
    const tilesetUuids = [...new Set(props.layer.data.map((tile) => (tile as Tile).brush.tileset))];
    const tilesetImages = new Map<string, HTMLImageElement>();

    // Load all tileset images
    await Promise.all(
        tilesetUuids.map(async (tilesetUuid) => {
            const tileset = tileSetStore.tileSets.find((ts) => ts.uuid === tilesetUuid);
            if (!tileset || !tileset.imageUrl) {
                console.warn('Tileset not found or no imageUrl:', tilesetUuid);
                return;
            }

            const img = new Image();
            img.crossOrigin = 'anonymous';

            return new Promise<void>((resolve) => {
                img.onload = () => {
                    tilesetImages.set(tilesetUuid, img);
                    resolve();
                };
                img.onerror = (err) => {
                    console.error('Failed to load tileset image:', tilesetUuid, err);
                    resolve();
                };
                img.src = tileset.imageUrl || '';
            });
        }),
    );

    // Draw each tile
    for (const tile of props.layer.data as Tile[]) {
        const tilesetImage = tilesetImages.get(tile.brush.tileset);
        if (!tilesetImage) {
            console.warn('No image for tileset:', tile.brush.tileset);
            continue;
        }

        const tileset = tileSetStore.tileSets.find((ts) => ts.uuid === tile.brush.tileset);
        if (!tileset) {
            console.warn('Tileset not found:', tile.brush.tileset);
            continue;
        }

        const tileWidth = tileset.tileWidth || 32;
        const tileHeight = tileset.tileHeight || 32;

        // Source coordinates in the tileset image
        const sourceX = tile.brush.tileX * tileWidth;
        const sourceY = tile.brush.tileY * tileHeight;

        // Destination coordinates on the canvas
        const destX = tile.x * store.mapMetadata.tileWidth;
        const destY = tile.y * store.mapMetadata.tileHeight;

        // Draw the tile
        ctx.drawImage(tilesetImage, sourceX, sourceY, tileWidth, tileHeight, destX, destY, store.mapMetadata.tileWidth, store.mapMetadata.tileHeight);
    }
};

const renderFieldTypeLayer = async (ctx: CanvasRenderingContext2D) => {
    // Ensure field types are loaded
    if (!store.fieldTypesLoaded) {
        await store.loadFieldTypes();
    }

    // Draw each field type
    for (const fieldTypeTile of props.layer.data as FieldTypeTile[]) {
        // Skip invalid field type tiles
        if (!fieldTypeTile || typeof fieldTypeTile.fieldType !== 'number') {
            continue;
        }

        const fieldType = store.fieldTypes.find((ft) => ft.id === fieldTypeTile.fieldType);
        if (!fieldType) {
            continue; // Skip if field type not found
        }

        // Destination coordinates on the canvas
        const destX = fieldTypeTile.x * store.mapMetadata.tileWidth;
        const destY = fieldTypeTile.y * store.mapMetadata.tileHeight;

        // Draw field type as a colored rectangle
        ctx.fillStyle = fieldType.color;
        ctx.fillRect(destX, destY, store.mapMetadata.tileWidth, store.mapMetadata.tileHeight);

        // Add a border to make it more visible
        ctx.strokeStyle = '#000000';
        ctx.lineWidth = 1;
        ctx.strokeRect(destX, destY, store.mapMetadata.tileWidth, store.mapMetadata.tileHeight);

        // Add field type name as text
        ctx.fillStyle = '#000000';
        ctx.font = '8px Arial';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(fieldType.name, destX + store.mapMetadata.tileWidth / 2, destY + store.mapMetadata.tileHeight / 2);
    }
};

// Expose render method for parent component
defineExpose({
    renderLayer,
});
</script>

<template>
    <canvas
        ref="canvasRef"
        class="absolute transition-opacity duration-150 ease-in-out"
        :class="{
            'opacity-100': layer.visible,
            'opacity-0': !layer.visible,
        }"
        :style="{
            'z-index': layer.z,
            width: store.canvasWidth + 'px',
            height: store.canvasHeight + 'px',
        }"
        :width="store.canvasWidth"
        :height="store.canvasHeight"
    >
    </canvas>
</template>
