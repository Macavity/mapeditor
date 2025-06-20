<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { MapLayer } from '@/types/MapLayer';
import { TileMap } from '@/types/TileMap';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Edit } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    map: TileMap;
    layers: MapLayer[];
    playerPosition: { x: number; y: number };
}>();

const error = ref<string | null>(null);
const isLoading = ref(false);

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

const sortedLayers = computed(() => {
    return [...props.layers].sort((a, b) => a.z - b.z);
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
    left: `${props.playerPosition.x * props.map.tile_width}px`,
    top: `${props.playerPosition.y * props.map.tile_height}px`,
    width: `${props.map.tile_width}px`,
    height: `${props.map.tile_height}px`,
    backgroundColor: 'red',
    borderRadius: '50%',
    zIndex: 4,
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
                                    'bg-blue-500': layer.type === 'background',
                                    'bg-green-500': layer.type === 'floor',
                                    'bg-red-500': layer.type === 'player',
                                    'bg-purple-500': layer.type === 'sky',
                                    'bg-gray-500': layer.type === 'field_type',
                                }"
                            ></div>
                            <span class="truncate">{{ layer.name }} (Z: {{ layer.z }})</span>
                        </div>
                    </div>

                    <!-- Map -->
                    <div
                        class="relative overflow-hidden rounded-lg border-2 border-gray-300 bg-gray-100 dark:border-gray-600 dark:bg-gray-800"
                        :style="mapStyle"
                    >
                        <!-- Render all layers as images -->
                        <template v-for="layer in sortedLayers" :key="layer.uuid">
                            <div v-if="layer.type !== 'player'" :style="getLayerStyle(layer)">
                                <img
                                    :src="getLayerImageUrl(layer.uuid)"
                                    :alt="`${layer.name} layer`"
                                    class="h-full w-full object-cover"
                                    :title="`${layer.name} - Z: ${layer.z}`"
                                    @error="error = `Failed to load layer image for ${layer.name}`"
                                />
                            </div>

                            <!-- Player layer -->
                            <div v-else-if="layer.type === 'player'" :style="getLayerStyle(layer)">
                                <div :style="getPlayerStyle()" :title="`${layer.name} - Position: (${playerPosition.x}, ${playerPosition.y})`">
                                    <!-- Player representation -->
                                    <div>P</div>
                                </div>
                            </div>
                        </template>

                        <!-- Map info overlay -->
                        <div class="absolute right-2 bottom-2 rounded bg-black/70 p-2 text-xs text-white">
                            <div>Size: {{ map.width }}×{{ map.height }}</div>
                            <div>Tile: {{ map.tile_width }}×{{ map.tile_height }}</div>
                            <div>Total: {{ map.width * map.height }} tiles</div>
                            <div>Player: ({{ playerPosition.x }}, {{ playerPosition.y }})</div>
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
                            'border-blue-500 bg-blue-50 dark:bg-blue-950': layer.type === 'background',
                            'border-green-500 bg-green-50 dark:bg-green-950': layer.type === 'floor',
                            'border-red-500 bg-red-50 dark:bg-red-950': layer.type === 'player',
                            'border-purple-500 bg-purple-50 dark:bg-purple-950': layer.type === 'sky',
                            'border-gray-500 bg-gray-50 dark:bg-gray-950': layer.type === 'field_type',
                        }"
                    >
                        <div class="mb-2 flex items-center gap-2">
                            <div
                                :class="{
                                    'h-4 w-4 rounded-full': true,
                                    'bg-blue-500': layer.type === 'background',
                                    'bg-green-500': layer.type === 'floor',
                                    'bg-red-500': layer.type === 'player',
                                    'bg-purple-500': layer.type === 'sky',
                                    'bg-gray-500': layer.type === 'field_type',
                                }"
                            ></div>
                            <span class="font-semibold">{{ layer.name }}</span>
                        </div>
                        <div class="text-muted-foreground text-sm">
                            <div>Type: {{ layer.type }}</div>
                            <div>Z-Index: {{ layer.z }}</div>
                            <div v-if="layer.type !== 'player'">
                                <img
                                    :src="getLayerImageUrl(layer.uuid)"
                                    :alt="`${layer.name} preview`"
                                    class="mt-2 h-16 w-full rounded border object-cover"
                                    @error="error = `Failed to load preview for ${layer.name}`"
                                />
                            </div>
                            <div v-else>Player Position: ({{ playerPosition.x }}, {{ playerPosition.y }})</div>
                            <div>Opacity: {{ layer.opacity }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
