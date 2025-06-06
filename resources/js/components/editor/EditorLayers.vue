<script setup lang="ts">
import { useEditorStore } from '@/stores/editorStore';
import { MapLayer, MapLayerType } from '@/types/MapLayer';
import { ChevronRight, Cloud, Eye, EyeOff, Layers, List, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

const store = useEditorStore();
const isCreatingLayer = ref(false);
const isDeletingLayer = ref(false);
const showDeleteConfirm = ref(false);
const layerToDelete = ref<MapLayer | null>(null);

const isSky = (layer: MapLayer) => layer.type === MapLayerType.Sky;
const isBackground = (layer: MapLayer) => layer.type === MapLayerType.Background;
const isFloor = (layer: MapLayer) => layer.type === MapLayerType.Floor;

const createSkyLayer = async () => {
    if (!store.canCreateLayer.sky || isCreatingLayer.value) return;

    isCreatingLayer.value = true;
    try {
        await store.createSkyLayer();
    } catch (error) {
        console.error('Failed to create sky layer:', error);
        // You could add a toast notification here
    } finally {
        isCreatingLayer.value = false;
    }
};

const createFloorLayer = async () => {
    if (!store.canCreateLayer.floor || isCreatingLayer.value) return;

    isCreatingLayer.value = true;
    try {
        await store.createFloorLayer();
    } catch (error) {
        console.error('Failed to create floor layer:', error);
        // You could add a toast notification here
    } finally {
        isCreatingLayer.value = false;
    }
};

const handleDeleteLayer = (layer: MapLayer) => {
    const tileCount = store.getTileCount(layer.uuid);

    if (tileCount === 0) {
        // Delete immediately if layer is empty
        deleteLayer(layer);
    } else {
        // Show confirmation dialog if layer has data
        layerToDelete.value = layer;
        showDeleteConfirm.value = true;
    }
};

const deleteLayer = async (layer: MapLayer) => {
    if (isDeletingLayer.value) return;

    isDeletingLayer.value = true;
    try {
        const result = await store.deleteLayer(layer.uuid);
        if (!result.success) {
            console.error('Failed to delete layer:', result.error);
            // You could add a toast notification here
        }
    } catch (error) {
        console.error('Failed to delete layer:', error);
        // You could add a toast notification here
    } finally {
        isDeletingLayer.value = false;
        showDeleteConfirm.value = false;
        layerToDelete.value = null;
    }
};

const confirmDelete = () => {
    if (layerToDelete.value) {
        deleteLayer(layerToDelete.value);
    }
};

const cancelDelete = () => {
    showDeleteConfirm.value = false;
    layerToDelete.value = null;
};
</script>

<template>
    <div class="flex h-full flex-col rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <!-- Header -->
        <div class="border-b border-gray-200 p-4 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <List class="h-5 w-5" />
                    <h3 class="text-lg font-semibold">Layers</h3>
                </div>

                <!-- Add Layer Buttons -->
                <div class="flex gap-1">
                    <button
                        @click="createSkyLayer"
                        :disabled="!store.canCreateLayer.sky || isCreatingLayer"
                        class="flex items-center gap-1 rounded-md bg-blue-500 px-2 py-1 text-xs font-medium text-white transition-colors hover:bg-blue-600 disabled:cursor-not-allowed disabled:bg-gray-300 disabled:text-gray-500"
                        title="Add Sky Layer"
                    >
                        <Cloud class="h-3 w-3" />
                        Sky
                    </button>
                    <button
                        @click="createFloorLayer"
                        :disabled="!store.canCreateLayer.floor || isCreatingLayer"
                        class="flex items-center gap-1 rounded-md bg-green-500 px-2 py-1 text-xs font-medium text-white transition-colors hover:bg-green-600 disabled:cursor-not-allowed disabled:bg-gray-300 disabled:text-gray-500"
                        title="Add Floor Layer"
                    >
                        <Layers class="h-3 w-3" />
                        Floor
                    </button>
                </div>
            </div>

            <!-- Layer counts -->
            <div class="mt-2 flex gap-4 text-xs text-gray-600 dark:text-gray-400">
                <span>Sky: {{ store.layerCounts.sky }}/{{ store.layerLimits.sky }}</span>
                <span>Floor: {{ store.layerCounts.floor }}/{{ store.layerLimits.floor }}</span>
            </div>
        </div>

        <!-- Body -->
        <div class="flex-1 overflow-y-auto p-2">
            <!-- Loading indicator -->
            <div
                v-if="isCreatingLayer"
                class="mb-2 flex items-center gap-2 rounded-lg bg-blue-50 p-3 text-sm text-blue-700 dark:bg-blue-900/20 dark:text-blue-300"
            >
                <div class="h-4 w-4 animate-spin rounded-full border-2 border-blue-600 border-t-transparent"></div>
                Creating layer...
            </div>

            <ul class="space-y-1">
                <li
                    v-for="layer in store.layers"
                    :key="layer.uuid"
                    @click="store.activateLayer(layer.uuid)"
                    class="flex cursor-pointer items-center gap-2 rounded-lg p-3 transition-colors hover:bg-gray-100 dark:hover:bg-gray-700"
                    :class="{
                        'bg-gray-100 dark:bg-gray-700': layer.uuid === store.activeLayer,
                    }"
                >
                    <!-- Active indicator -->
                    <div class="flex w-5 justify-center">
                        <ChevronRight v-if="layer.uuid === store.activeLayer" class="text-primary h-4 w-4" />
                    </div>

                    <!-- Visibility toggle -->
                    <button
                        @click.stop="store.toggleLayerVisibility(layer.uuid)"
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
                        <span v-if="isFloor(layer)" class="rounded-full bg-green-500 px-2 py-1 text-xs font-medium text-white"> Floor </span>
                    </div>

                    <!-- Delete button -->
                    <button
                        @click.stop="handleDeleteLayer(layer)"
                        :disabled="isDeletingLayer"
                        class="flex w-5 justify-center text-gray-400 hover:text-red-600 disabled:cursor-not-allowed disabled:text-gray-300 dark:text-gray-500 dark:hover:text-red-400"
                        title="Delete layer"
                    >
                        <Trash2 class="h-4 w-4" />
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <!-- Delete Confirmation Dialog -->
    <div v-if="showDeleteConfirm" class="bg-opacity-50 fixed inset-0 z-50 flex items-center justify-center bg-black" @click="cancelDelete">
        <div @click.stop class="max-w-md rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 rounded-full bg-red-100 p-2 dark:bg-red-900/20">
                    <Trash2 class="h-6 w-6 text-red-600 dark:text-red-400" />
                </div>

                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Delete Layer</h3>

                    <div v-if="layerToDelete" class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        <p>
                            Are you sure you want to delete the layer <strong>"{{ layerToDelete.name }}"</strong>?
                        </p>
                        <p class="mt-1 text-red-600 dark:text-red-400">
                            This will permanently delete {{ store.getTileCount(layerToDelete.uuid) }}
                            {{ store.getTileCount(layerToDelete.uuid) === 1 ? 'tile' : 'tiles' }}.
                        </p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-500">This action cannot be undone.</p>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button
                    @click="cancelDelete"
                    :disabled="isDeletingLayer"
                    class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                >
                    Cancel
                </button>
                <button
                    @click="confirmDelete"
                    :disabled="isDeletingLayer"
                    class="flex items-center gap-2 rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <div v-if="isDeletingLayer" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></div>
                    <Trash2 v-else class="h-4 w-4" />
                    {{ isDeletingLayer ? 'Deleting...' : 'Delete Layer' }}
                </button>
            </div>
        </div>
    </div>
</template>
