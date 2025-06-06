<script setup lang="ts">
import { useEditorStore } from '@/stores/editorStore';
import { MapLayer, MapLayerType } from '@/types/MapLayer';
import { ChevronRight, Eye, EyeOff, List } from 'lucide-vue-next';

const store = useEditorStore();

const isSky = (layer: MapLayer) => layer.type === MapLayerType.Sky;
const isBackground = (layer: MapLayer) => layer.type === MapLayerType.Background;

const toggleLayerVisibility = (layerId: number) => {
    store.toggleLayerVisibility(layerId);
};
</script>

<template>
    <div class="flex h-full flex-col rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <!-- Header -->
        <div class="border-b border-gray-200 p-4 dark:border-gray-700">
            <div class="flex items-center gap-2">
                <List class="h-5 w-5" />
                <h3 class="text-lg font-semibold">Layers</h3>
            </div>
        </div>

        <!-- Body -->
        <div class="flex-1 overflow-y-auto p-2">
            <ul class="space-y-1">
                <li
                    v-for="layer in store.layers"
                    :key="layer.id"
                    @click="store.activateLayer(layer.id)"
                    class="flex cursor-pointer items-center gap-2 rounded-lg p-3 transition-colors hover:bg-gray-100 dark:hover:bg-gray-700"
                    :class="{
                        'bg-gray-100 dark:bg-gray-700': layer.id === store.activeLayer,
                    }"
                >
                    <!-- Active indicator -->
                    <div class="flex w-5 justify-center">
                        <ChevronRight v-if="layer.id === store.activeLayer" class="text-primary h-4 w-4" />
                    </div>

                    <!-- Visibility toggle -->
                    <button
                        @click.stop="toggleLayerVisibility(layer.id)"
                        class="flex w-5 justify-center text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                        <Eye v-if="layer.visible" class="h-4 w-4" />
                        <EyeOff v-else class="h-4 w-4" />
                    </button>

                    <!-- Layer name -->
                    <span class="flex-1 text-sm font-medium">{{ layer.name }}</span>

                    <!-- Type badges -->
                    <div class="flex gap-1">
                        <span v-if="isBackground(layer)" class="rounded-full bg-gray-500 px-2 py-1 text-xs font-medium text-white"> Bg </span>
                        <span v-if="isSky(layer)" class="rounded-full bg-blue-500 px-2 py-1 text-xs font-medium text-white"> Sky </span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</template>
