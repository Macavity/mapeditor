<script setup lang="ts">
import { useEditorStore } from '@/stores/editorStore';
import { useTileSetStore } from '@/stores/tileSetStore';
import { nextTick, onMounted, ref, watch } from 'vue';

const store = useEditorStore();
const tileSetStore = useTileSetStore();

const showBrush = ref(false);
const brushPosition = ref({ x: 0, y: 0 });
const canvasRefs = ref<{ [key: string]: HTMLCanvasElement }>({});

const tryRenderLayers = () => {
    if (store.loaded && store.layers.length > 0) {
        nextTick(() => {
            renderAllLayers();
        });
    }
};

const renderSpecificLayer = (layerUuid: string) => {
    if (store.loaded) {
        nextTick(() => {
            renderLayer(layerUuid);
        });
    }
};

onMounted(tryRenderLayers);

// Watch for changes in layer count (initial load, add/remove layers)
watch(() => store.layers.length, tryRenderLayers);

// Watch each layer's data individually for efficiency
watch(
    () => store.layers,
    (newLayers, oldLayers) => {
        if (!store.loaded || !newLayers || !oldLayers) return;

        // Check each layer for data changes
        newLayers.forEach((newLayer, index) => {
            const oldLayer = oldLayers[index];
            if (!oldLayer) return;

            // Check if this layer's data changed (compare array lengths first for performance)
            if (newLayer.data?.length !== oldLayer.data?.length || JSON.stringify(newLayer.data) !== JSON.stringify(oldLayer.data)) {
                renderSpecificLayer(newLayer.uuid);
            }

            // Check if visibility/opacity changed
            if (newLayer.visible !== oldLayer.visible || newLayer.opacity !== oldLayer.opacity) {
                renderSpecificLayer(newLayer.uuid);
            }
        });
    },
    { deep: true },
);

// Also watch for when the active layer changes to ensure immediate rendering
watch(
    () => store.activeLayer,
    (newActiveLayer) => {
        if (newActiveLayer && store.loaded) {
            // Small delay to ensure the layer data update has been processed
            setTimeout(() => {
                renderSpecificLayer(newActiveLayer);
            }, 10);
        }
    },
);

const setCanvasRef = (el: HTMLCanvasElement | null, layerUuid: string) => {
    if (el) {
        canvasRefs.value[layerUuid] = el;
    }
};

const renderAllLayers = async () => {
    // Wait a bit for canvas refs to be set up
    await nextTick();

    // Check if we have canvas refs for all layers
    const missingCanvases = store.layers.filter((layer) => !canvasRefs.value[layer.uuid]);
    if (missingCanvases.length > 0) {
        console.warn(
            'Missing canvas refs for layers:',
            missingCanvases.map((l) => l.uuid),
        );
        // Retry after a short delay
        setTimeout(() => {
            renderAllLayers();
        }, 100);
        return;
    }

    for (const layer of store.layers) {
        await renderLayer(layer.uuid);
    }
};

const renderLayer = async (layerUuid: string) => {
    const canvas = canvasRefs.value[layerUuid];
    if (!canvas) {
        console.warn('Canvas not found for layer', layerUuid);
        return;
    }

    const ctx = canvas.getContext('2d');
    if (!ctx) {
        console.error('Context not found for layer', layerUuid);
        return;
    }

    // Clear the canvas
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    const layer = store.layers.find((l) => l.uuid === layerUuid);
    if (!layer || !layer.data || !Array.isArray(layer.data) || layer.data.length === 0) {
        return;
    }

    // Ensure tilesets are loaded first
    if (tileSetStore.tileSets.length === 0) {
        await tileSetStore.loadTileSets();
    }

    // Load all unique tilesets used in this layer
    const tilesetUuids = [...new Set(layer.data.map((tile) => tile.brush.tileset))];
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
    for (const tile of layer.data) {
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

const onMouseEnter = () => {
    showBrush.value = true;
};

const onMouseLeave = () => {
    showBrush.value = false;
};

const onMouseMove = (event: MouseEvent) => {
    if (!showBrush.value) return;

    const target = event.currentTarget as HTMLElement;
    const rect = target.getBoundingClientRect();

    // Calculate mouse position relative to canvas
    const mouseX = event.clientX - rect.left;
    const mouseY = event.clientY - rect.top;

    // Get tile dimensions from the map
    const tileWidth = store.mapMetadata.tileWidth;
    const tileHeight = store.mapMetadata.tileHeight;

    // Calculate tile coordinates (snap to grid)
    const tileX = Math.floor(mouseX / tileWidth);
    const tileY = Math.floor(mouseY / tileHeight);

    // Convert back to pixel coordinates (snapped to tile grid)
    const pixelX = tileX * tileWidth;
    const pixelY = tileY * tileHeight;

    // Update brush position
    brushPosition.value = { x: pixelX, y: pixelY };
};

const onCanvasClick = (event: MouseEvent) => {
    // Only place tile if we have a brush selection
    if (!store.brushSelection.tilesetUuid || !store.brushSelection.backgroundImage) {
        return;
    }

    const target = event.currentTarget as HTMLElement;
    const rect = target.getBoundingClientRect();

    // Calculate mouse position relative to canvas
    const mouseX = event.clientX - rect.left;
    const mouseY = event.clientY - rect.top;

    // Get tile dimensions from the map
    const tileWidth = store.mapMetadata.tileWidth;
    const tileHeight = store.mapMetadata.tileHeight;

    // Calculate tile coordinates (snap to grid)
    const tileX = Math.floor(mouseX / tileWidth);
    const tileY = Math.floor(mouseY / tileHeight);

    // Place the tile
    store.placeTile(tileX, tileY);

    // Ensure the active layer is re-rendered immediately
    if (store.activeLayer) {
        nextTick(() => {
            renderLayer(store.activeLayer!);
        });
    }
};
</script>

<template>
    <div
        @mousemove="onMouseMove"
        @mouseenter="onMouseEnter"
        @mouseleave="onMouseLeave"
        @click="onCanvasClick"
        class="border-opacity-50 relative border"
        :style="{
            width: store.canvasWidth + 'px',
            height: store.canvasHeight + 'px',
        }"
    >
        <!-- Selection/Brush cursor -->
        <div
            v-show="showBrush"
            class="pointer-events-none absolute opacity-50 transition-opacity duration-150"
            :style="{
                left: brushPosition.x + 'px',
                top: brushPosition.y + 'px',
                zIndex: 99,
            }"
        >
            <div
                id="brush"
                class="border border-black bg-blue-200 dark:bg-blue-400"
                :style="{
                    width: store.mapMetadata.tileWidth + 'px',
                    height: store.mapMetadata.tileHeight + 'px',
                    background: store.brushSelection.backgroundImage ? 'url(\'' + store.brushSelection.backgroundImage + '\') no-repeat' : undefined,
                    backgroundPosition: store.brushSelection.backgroundImage ? store.brushSelection.backgroundPosition : undefined,
                }"
            ></div>
        </div>

        <!-- Tilemap layers container -->
        <div class="relative">
            <canvas
                v-for="layer in store.layers"
                :key="layer.uuid"
                :ref="(el) => setCanvasRef(el as HTMLCanvasElement, layer.uuid)"
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
        </div>

        <!-- Grid overlay (when enabled) -->
        <div
            v-if="store.showGrid"
            class="pointer-events-none absolute inset-0 opacity-40"
            :style="{
                width: store.canvasWidth + 'px',
                height: store.canvasHeight + 'px',
                backgroundSize: store.mapMetadata.tileWidth + 'px ' + store.mapMetadata.tileHeight + 'px',
                backgroundImage: `
                    repeating-linear-gradient(0deg, black, black 1px, transparent 1px, transparent ${store.mapMetadata.tileWidth}px),
                    repeating-linear-gradient(-90deg, black, black 1px, transparent 1px, transparent ${store.mapMetadata.tileHeight}px)
                `,
            }"
        ></div>
    </div>
</template>

<style lang="scss" scoped>
.selection {
    z-index: 99;
    box-shadow: inset 0px 0px 0px 1px theme('colors.black');
}
</style>
