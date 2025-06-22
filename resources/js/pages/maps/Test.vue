<script setup lang="ts">
import { ToggleButton } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { isFieldTypeLayer, isFieldTypeTile, isObjectLayer, MapLayer, MapLayerType } from '@/types/MapLayer';
import { TileMap } from '@/types/TileMap';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowDown, ArrowLeft as ArrowLeftIcon, ArrowRight, ArrowUp, Edit, Grid } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps<{
    map: TileMap;
    layers: MapLayer[];
    playerPosition: { x: number; y: number };
}>();

const error = ref<string | null>(null);

// Reactive player position
const playerPos = ref({ x: props.playerPosition.x, y: props.playerPosition.y });

// Check if player position came from an object layer
const playerPositionSource = computed(() => {
    const centerX = Math.floor(props.map.width / 2);
    const centerY = Math.floor(props.map.height / 2);

    // If position matches center, it likely came from fallback
    if (playerPos.value.x === centerX && playerPos.value.y === centerY) {
        return 'center';
    }

    return 'object-layer';
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Maps',
        href: '/manage-maps',
    },
    {
        title: props.map.name,
        href: `/maps/${props.map.uuid}/edit`,
    },
    {
        title: 'Test',
        href: `/maps/${props.map.uuid}/test`,
    },
];

// Computed properties for rendering
const mapStyle = computed(() => ({
    width: `${props.map.width * props.map.tile_width}px`,
    height: `${props.map.height * props.map.tile_height}px`,
}));

// Viewport size (fixed, or clamp to map size)
const viewportWidth = computed(() => Math.min(640, props.map.width * props.map.tile_width));
const viewportHeight = computed(() => Math.min(480, props.map.height * props.map.tile_height));

// Calculate the player's z-index to position it between floor and sky layers
const playerZIndex = computed(() => {
    const floorLayers = props.layers.filter((layer) => layer.type === MapLayerType.Floor);

    // Find the highest floor layer z-index
    const highestFloorZ = floorLayers.length > 0 ? Math.max(...floorLayers.map((layer) => layer.z)) : 0;

    // Position player between floor and sky layers
    return highestFloorZ + 1;
});

// Calculate the offset to center the player in the viewport
const mapOffset = computed(() => {
    // Center of the viewport
    const centerX = viewportWidth.value / 2;
    const centerY = viewportHeight.value / 2;

    // Always center the player in the viewport
    const offsetX = centerX - (playerPos.value.x + 0.5) * props.map.tile_width;
    const offsetY = centerY - (playerPos.value.y + 0.5) * props.map.tile_height;

    return {
        transform: `translate(${offsetX}px, ${offsetY}px)`,
    };
});

const sortedLayers = computed(() => {
    return [...props.layers].sort((a, b) => a.z - b.z);
});

// Get field type layer for collision detection
const fieldTypeLayer = computed(() => {
    return props.layers.find((layer) => layer.type === MapLayerType.FieldType);
});

// Get object layers for display
const objectLayers = computed(() => {
    return props.layers.filter((layer) => layer.type === MapLayerType.Object);
});

const getLayerStyle = (layer: MapLayer) => ({
    zIndex: layer.z,
    opacity: layer.opacity,
});

const getPlayerStyle = () => ({
    left: `${playerPos.value.x * props.map.tile_width}px`,
    top: `${playerPos.value.y * props.map.tile_height}px`,
    width: `${props.map.tile_width}px`,
    height: `${props.map.tile_height}px`,
    zIndex: playerZIndex.value,
});

const getLayerImageUrl = (layerUuid: string) => {
    return `/layers/${layerUuid}.png`;
};

// Movement functions
const canMoveTo = (x: number, y: number): boolean => {
    // Check map boundaries
    if (x < 0 || x >= props.map.width || y < 0 || y >= props.map.height) {
        return false;
    }

    // Check field type collision (field type 2 = no entry)
    if (fieldTypeLayer.value && isFieldTypeLayer(fieldTypeLayer.value)) {
        const fieldTypeTile = fieldTypeLayer.value.data.find((tile: any) => tile.x === x && tile.y === y);
        if (fieldTypeTile && fieldTypeTile.fieldType === 2) {
            return false; // Blocked by field type 2 (no entry)
        }
    }

    // Check object collision (solid objects block movement)
    for (const layer of objectLayers.value) {
        if (isObjectLayer(layer) && layer.data) {
            const object = layer.data.find((obj) => obj.x === x && obj.y === y);
            if (object) {
                // For now, we'll assume objectType 1 (player) is not solid
                // and other object types are solid. In a real implementation,
                // you'd want to check the object type's is_solid property
                if (object.objectType !== 1) {
                    return false; // Blocked by solid object
                }
            }
        }
    }

    return true;
};

const movePlayer = (dx: number, dy: number) => {
    const newX = playerPos.value.x + dx;
    const newY = playerPos.value.y + dy;

    if (canMoveTo(newX, newY)) {
        playerPos.value.x = newX;
        playerPos.value.y = newY;
    }
};

const handleKeydown = (event: KeyboardEvent) => {
    switch (event.key) {
        case 'ArrowUp':
        case 'w':
        case 'W':
            event.preventDefault();
            movePlayer(0, -1);
            break;
        case 'ArrowDown':
        case 's':
        case 'S':
            event.preventDefault();
            movePlayer(0, 1);
            break;
        case 'ArrowLeft':
        case 'a':
        case 'A':
            event.preventDefault();
            movePlayer(-1, 0);
            break;
        case 'ArrowRight':
        case 'd':
        case 'D':
            event.preventDefault();
            movePlayer(1, 0);
            break;
    }
};

// Keyboard event listeners
onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
});

// Movement buttons
const moveUp = () => movePlayer(0, -1);
const moveDown = () => movePlayer(0, 1);
const moveLeft = () => movePlayer(-1, 0);
const moveRight = () => movePlayer(1, 0);

// Field type layer debug toggle
const showFieldTypeLayer = ref(false);
const fieldTypeOverlayImage = ref<string | null>(null);

// Generate field type overlay image
const generateFieldTypeOverlay = () => {
    if (!showFieldTypeLayer.value || !fieldTypeLayer.value || !isFieldTypeLayer(fieldTypeLayer.value)) {
        fieldTypeOverlayImage.value = null;
        return;
    }

    // Only create canvas in browser environment
    if (typeof document === 'undefined') {
        fieldTypeOverlayImage.value = null;
        return;
    }

    const canvas = document.createElement('canvas');
    canvas.width = props.map.width * props.map.tile_width;
    canvas.height = props.map.height * props.map.tile_height;
    const ctx = canvas.getContext('2d');

    if (!ctx) {
        fieldTypeOverlayImage.value = null;
        return;
    }

    // Draw red overlay for field type 2 tiles
    fieldTypeLayer.value.data.forEach((tile) => {
        if (isFieldTypeTile(tile) && tile.fieldType === 2) {
            ctx.fillStyle = 'rgba(255, 0, 0, 0.4)';
            ctx.strokeStyle = 'rgba(255, 0, 0, 0.7)';
            ctx.lineWidth = 1;

            const x = tile.x * props.map.tile_width;
            const y = tile.y * props.map.tile_height;
            const width = props.map.tile_width;
            const height = props.map.tile_height;

            ctx.fillRect(x, y, width, height);
            ctx.strokeRect(x, y, width, height);
        }
    });

    fieldTypeOverlayImage.value = canvas.toDataURL();
};

// Watch for changes and regenerate overlay
watch(
    [showFieldTypeLayer, fieldTypeLayer],
    () => {
        generateFieldTypeOverlay();
    },
    { immediate: true },
);
</script>

<template>
    <Head :title="`Test ${map.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">{{ map.name }} - Test Mode</h1>
                    <p class="text-muted-foreground">
                        Testing map with {{ layers.length }} layers
                        <span v-if="playerPositionSource === 'object-layer'" class="font-medium text-green-600">
                            • Player position from object layer
                        </span>
                        <span v-else class="text-gray-500"> • Player position at center (default) </span>
                    </p>
                </div>
                <div class="flex items-center gap-4">
                    <Link :href="`/maps/${map.uuid}/edit`" class="btn btn-outline flex items-center gap-2">
                        <Edit class="h-4 w-4" />
                        Edit Map
                    </Link>
                </div>
            </div>

            <div class="mb-2 flex gap-2">
                <ToggleButton
                    :icon="Grid"
                    :text="showFieldTypeLayer ? 'Hide Field Type Layer' : 'Show Field Type Layer'"
                    :active="showFieldTypeLayer"
                    @click="showFieldTypeLayer = !showFieldTypeLayer"
                />
            </div>

            <!-- Map Container -->
            <div class="flex flex-1 gap-4">
                <!-- Left Sidebar -->
                <aside class="flex min-h-0 w-80 shrink-0 flex-col gap-4 transition-all duration-200">
                    <section class="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4">
                        <div class="mb-2 text-sm font-bold">Layers ({{ layers.length }})</div>
                        <div v-for="layer in sortedLayers" :key="layer.uuid" class="mb-1 flex items-center gap-2">
                            <div
                                :class="{
                                    'h-3 w-3 rounded-full': true,
                                    'bg-blue-500': layer.type === MapLayerType.Background,
                                    'bg-green-500': layer.type === MapLayerType.Floor,
                                    'bg-red-500': layer.type === MapLayerType.Object,
                                    'bg-purple-500': layer.type === MapLayerType.Sky,
                                    'bg-gray-500': layer.type === MapLayerType.FieldType,
                                }"
                            ></div>
                            <span class="truncate text-sm">{{ layer.name }} (Z: {{ layer.z }})</span>
                        </div>
                    </section>

                    <section class="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4">
                        <div class="mb-2 text-sm font-bold">Map Info</div>
                        <div class="space-y-1 text-sm">
                            <div>Size: {{ map.width }}×{{ map.height }}</div>
                            <div>Tile: {{ map.tile_width }}×{{ map.tile_height }}</div>
                            <div>Total: {{ map.width * map.height }} tiles</div>
                        </div>
                    </section>

                    <section class="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4">
                        <div class="mb-2 text-sm font-bold">Player</div>
                        <div class="space-y-1 text-sm">
                            <div>Position: ({{ playerPos.x }}, {{ playerPos.y }})</div>
                            <div class="text-muted-foreground">
                                Source: {{ playerPositionSource === 'object-layer' ? 'Object Layer' : 'Center (default)' }}
                            </div>
                        </div>
                    </section>
                </aside>

                <!-- Main Map Viewport -->
                <section class="border-sidebar-border/70 dark:border-sidebar-border relative min-h-0 max-w-[calc(100%-40rem)] flex-1 overflow-auto">
                    <div class="flex h-full items-center justify-center">
                        <!-- Map viewport (fixed size, relative for centering player) -->
                        <div
                            class="relative overflow-hidden rounded-lg border-2 border-gray-300 bg-gray-100 dark:border-gray-600 dark:bg-gray-800"
                            :style="{ width: viewportWidth + 'px', height: viewportHeight + 'px' }"
                        >
                            <!-- Map layers, shifted to center player -->
                            <div class="absolute top-0 left-0" :style="[mapStyle, mapOffset]">
                                <template v-for="layer in sortedLayers" :key="layer.uuid">
                                    <div
                                        v-if="layer.type !== MapLayerType.Object && layer.type !== MapLayerType.FieldType"
                                        class="absolute top-0 left-0 h-full w-full"
                                        :style="getLayerStyle(layer)"
                                    >
                                        <img
                                            :src="getLayerImageUrl(layer.uuid)"
                                            :alt="`${layer.name} layer`"
                                            class="h-full w-full object-cover"
                                            :title="`${layer.name} - Z: ${layer.z}`"
                                            @error="error = `Failed to load layer image for ${layer.name}`"
                                        />
                                    </div>
                                </template>

                                <!-- Field Type Layer Debug Overlay -->
                                <div
                                    v-if="showFieldTypeLayer && fieldTypeLayer && fieldTypeOverlayImage"
                                    class="pointer-events-none absolute top-0 left-0 h-full w-full"
                                    style="z-index: 100"
                                >
                                    <img
                                        :src="fieldTypeOverlayImage"
                                        :alt="'Field Type Overlay'"
                                        class="h-full w-full object-cover"
                                        :title="'Field Type Overlay'"
                                    />
                                </div>

                                <!-- Player layer positioned between floor and sky layers -->
                                <div
                                    class="absolute flex items-center justify-center rounded-full border-2 border-white bg-red-500 text-xs font-bold text-white shadow-lg"
                                    :style="getPlayerStyle()"
                                    title="Player"
                                >
                                    <div>P</div>
                                </div>
                            </div>

                            <!-- Error message -->
                            <div v-if="error" class="absolute inset-0 flex items-center justify-center bg-red-500/90 text-white">
                                <div class="text-center">
                                    <div class="mb-2 font-bold">Error Loading Map</div>
                                    <div class="text-sm">{{ error }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Right Sidebar -->
                <aside class="flex min-h-0 w-80 shrink-0 flex-col gap-4">
                    <section class="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4">
                        <div class="mb-2 text-center text-sm font-bold">Movement</div>
                        <div class="grid grid-cols-3 gap-1">
                            <!-- Top row -->
                            <div></div>
                            <button
                                @click="moveUp"
                                class="flex h-8 w-8 items-center justify-center rounded bg-blue-600 hover:bg-blue-700 active:bg-blue-800"
                                title="Move Up (W/↑)"
                            >
                                <ArrowUp class="h-4 w-4" />
                            </button>
                            <div></div>

                            <!-- Middle row -->
                            <button
                                @click="moveLeft"
                                class="flex h-8 w-8 items-center justify-center rounded bg-blue-600 hover:bg-blue-700 active:bg-blue-800"
                                title="Move Left (A/←)"
                            >
                                <ArrowLeftIcon class="h-4 w-4" />
                            </button>
                            <div class="flex h-8 w-8 items-center justify-center rounded bg-gray-600">
                                <div class="h-2 w-2 rounded-full bg-red-500"></div>
                            </div>
                            <button
                                @click="moveRight"
                                class="flex h-8 w-8 items-center justify-center rounded bg-blue-600 hover:bg-blue-700 active:bg-blue-800"
                                title="Move Right (D/→)"
                            >
                                <ArrowRight class="h-4 w-4" />
                            </button>

                            <!-- Bottom row -->
                            <div></div>
                            <button
                                @click="moveDown"
                                class="flex h-8 w-8 items-center justify-center rounded bg-blue-600 hover:bg-blue-700 active:bg-blue-800"
                                title="Move Down (S/↓)"
                            >
                                <ArrowDown class="h-4 w-4" />
                            </button>
                            <div></div>
                        </div>
                        <div class="text-muted-foreground mt-2 text-center text-xs">Use WASD or arrow keys</div>
                    </section>
                </aside>
            </div>
        </div>
    </AppLayout>
</template>
