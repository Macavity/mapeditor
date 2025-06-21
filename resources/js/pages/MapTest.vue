<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { isFieldTypeLayer, MapLayer, MapLayerType } from '@/types/MapLayer';
import { TileMap } from '@/types/TileMap';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowDown, ArrowLeft, ArrowLeft as ArrowLeftIcon, ArrowRight, ArrowUp, Edit } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps<{
    map: TileMap;
    layers: MapLayer[];
    playerPosition: { x: number; y: number };
}>();

const error = ref<string | null>(null);
const isLoading = ref(false);

// Reactive player position
const playerPos = ref({ x: props.playerPosition.x, y: props.playerPosition.y });

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
    const skyLayers = props.layers.filter((layer) => layer.type === MapLayerType.Sky);

    // Find the highest floor layer z-index
    const highestFloorZ = floorLayers.length > 0 ? Math.max(...floorLayers.map((layer) => layer.z)) : 0;

    // Find the lowest sky layer z-index
    const lowestSkyZ = skyLayers.length > 0 ? Math.min(...skyLayers.map((layer) => layer.z)) : highestFloorZ + 1;

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

const getLayerStyle = (layer: MapLayer) => ({
    position: 'absolute' as const,
    top: 0,
    left: 0,
    width: '100%',
    height: '100%',
    zIndex: layer.z,
    opacity: layer.opacity,
});

const getPlayerStyle = () => ({
    position: 'absolute' as const,
    left: `calc(50% - ${props.map.tile_width / 2}px)`,
    top: `calc(50% - ${props.map.tile_height / 2}px)`,
    width: `${props.map.tile_width}px`,
    height: `${props.map.tile_height}px`,
    backgroundColor: 'red',
    borderRadius: '50%',
    zIndex: playerZIndex.value,
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    color: 'white',
    fontWeight: 'bold',
    fontSize: '12px',
    border: '2px solid white',
    boxShadow: '0 0 4px rgba(0,0,0,0.5)',
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
</script>

<template>
    <Head :title="`Test ${map.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">{{ map.name }} - Test Mode</h1>
                    <p class="text-muted-foreground">Testing map with {{ layers.length }} layers</p>
                </div>
                <div class="flex gap-2">
                    <Link :href="`/maps/${map.uuid}/edit`" class="btn btn-outline flex items-center gap-2">
                        <Edit class="h-4 w-4" />
                        Edit Map
                    </Link>
                    <Link href="/manage-maps" class="btn btn-primary flex items-center gap-2">
                        <ArrowLeft class="h-4 w-4" />
                        Back to Maps
                    </Link>
                </div>
            </div>

            <!-- Map Container -->
            <div class="flex flex-1 items-center justify-center">
                <div class="flex items-center gap-4">
                    <!-- Layer info sidebar -->
                    <div class="min-w-[200px] rounded bg-black/70 p-3 text-xs text-white">
                        <div class="mb-2 font-bold">Layers ({{ layers.length }})</div>
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
                            <span class="truncate">{{ layer.name }} (Z: {{ layer.z }})</span>
                        </div>
                    </div>
                    <!-- Map info sidebar -->
                    <div class="min-w-[200px] rounded bg-black/70 p-3 text-xs text-white">
                        <div class="mb-2 font-bold">Map Info</div>
                        <div>Size: {{ map.width }}×{{ map.height }}</div>
                        <div>Tile: {{ map.tile_width }}×{{ map.tile_height }}</div>
                        <div>Total: {{ map.width * map.height }} tiles</div>
                        <div class="mt-2 font-bold">Player</div>
                        <div>Position: ({{ playerPos.x }}, {{ playerPos.y }})</div>
                    </div>

                    <!-- Map viewport (fixed size, relative for centering player) -->
                    <div
                        class="relative overflow-hidden rounded-lg border-2 border-gray-300 bg-gray-100 dark:border-gray-600 dark:bg-gray-800"
                        :style="{ width: viewportWidth + 'px', height: viewportHeight + 'px' }"
                    >
                        <!-- Map layers, shifted to center player -->
                        <div class="absolute top-0 left-0" :style="[mapStyle, mapOffset]">
                            <template v-for="layer in sortedLayers" :key="layer.uuid">
                                <div v-if="layer.type !== MapLayerType.Object" :style="getLayerStyle(layer)">
                                    <img
                                        :src="getLayerImageUrl(layer.uuid)"
                                        :alt="`${layer.name} layer`"
                                        class="h-full w-full object-cover"
                                        :title="`${layer.name} - Z: ${layer.z}`"
                                        @error="error = `Failed to load layer image for ${layer.name}`"
                                    />
                                </div>
                            </template>

                            <!-- Player layer positioned between floor and sky layers -->
                            <div
                                :style="{
                                    position: 'absolute',
                                    left: `${playerPos.x * props.map.tile_width}px`,
                                    top: `${playerPos.y * props.map.tile_height}px`,
                                    width: `${props.map.tile_width}px`,
                                    height: `${props.map.tile_height}px`,
                                    zIndex: playerZIndex,
                                    display: 'flex',
                                    alignItems: 'center',
                                    justifyContent: 'center',
                                    backgroundColor: 'red',
                                    borderRadius: '50%',
                                    color: 'white',
                                    fontWeight: 'bold',
                                    fontSize: '12px',
                                    border: '2px solid white',
                                    boxShadow: '0 0 4px rgba(0,0,0,0.5)',
                                }"
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

                    <!-- Movement Controls -->
                    <div class="min-w-[120px] rounded bg-black/70 p-3 text-white">
                        <div class="mb-2 text-center text-xs font-bold">Movement</div>
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
                        <div class="mt-2 text-center text-xs text-gray-300">Use WASD or arrow keys</div>
                    </div>
                </div>
            </div>

            <!-- Layer Details -->
            <div class="bg-card rounded-lg p-4">
                <h3 class="mb-3 text-lg font-semibold">Layer Details</h3>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="layer in sortedLayers"
                        :key="layer.uuid"
                        class="rounded-lg border p-3"
                        :class="{
                            'border-blue-500 bg-blue-50 dark:bg-blue-950': layer.type === MapLayerType.Background,
                            'border-green-500 bg-green-50 dark:bg-green-950': layer.type === MapLayerType.Floor,
                            'border-red-500 bg-red-50 dark:bg-red-950': layer.type === MapLayerType.Object,
                            'border-purple-500 bg-purple-50 dark:bg-purple-950': layer.type === MapLayerType.Sky,
                            'border-gray-500 bg-gray-50 dark:bg-gray-950': layer.type === MapLayerType.FieldType,
                        }"
                    >
                        <div class="mb-2 flex items-center gap-2">
                            <div
                                :class="{
                                    'h-4 w-4 rounded-full': true,
                                    'bg-blue-500': layer.type === MapLayerType.Background,
                                    'bg-green-500': layer.type === MapLayerType.Floor,
                                    'bg-red-500': layer.type === MapLayerType.Object,
                                    'bg-purple-500': layer.type === MapLayerType.Sky,
                                    'bg-gray-500': layer.type === MapLayerType.FieldType,
                                }"
                            ></div>
                            <span class="font-semibold">{{ layer.name }}</span>
                        </div>
                        <div class="text-muted-foreground text-sm">
                            <div>Type: {{ layer.type }}</div>
                            <div>Z-Index: {{ layer.z }}</div>
                            <div v-if="layer.type !== MapLayerType.Object">
                                <img
                                    :src="getLayerImageUrl(layer.uuid)"
                                    :alt="`${layer.name} preview`"
                                    class="mt-2 h-16 w-full rounded border object-cover"
                                    @error="error = `Failed to load preview for ${layer.name}`"
                                />
                            </div>
                            <div v-else>Player Position: ({{ playerPos.x }}, {{ playerPos.y }})</div>
                            <div>Opacity: {{ layer.opacity }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
