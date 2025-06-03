<script setup lang="ts">
import { useMapStore } from '@/stores/mapStore';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

const store = useMapStore();
const name = ref('');
const width = ref(20);
const height = ref(20);
const tileSize = ref(32);
const isCreating = ref(false);
const error = ref('');

async function createMap() {
    if (!name.value) {
        error.value = 'Please enter a map name';
        return;
    }

    isCreating.value = true;
    error.value = '';

    try {
        const newMap = await store.createMap({
            name: name.value,
            width: width.value,
            height: height.value,
            tile_width: tileSize.value,
            tile_height: tileSize.value,
        });

        // Reset form
        name.value = '';

        // Navigate to edit page
        router.visit(`/maps/${newMap.uuid}/edit`);
    } catch (e) {
        error.value = e instanceof Error ? e.message : 'Failed to create map';
    } finally {
        isCreating.value = false;
    }
}
</script>

<template>
    <div class="bg-card text-card-foreground">
        <div class="flex flex-col space-y-1.5 p-6">
            <h3 class="text-2xl leading-none font-semibold tracking-tight">Create New Map</h3>
            <p class="text-muted-foreground text-sm">Create a new map with custom dimensions.</p>
        </div>
        <div class="p-6 pt-0">
            <div class="space-y-4">
                <div class="space-y-2">
                    <label class="text-sm leading-none font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70"> Name </label>
                    <input
                        v-model="name"
                        type="text"
                        placeholder="Enter map name"
                        class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm file:border-0 file:bg-transparent file:text-sm file:font-medium focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                    />
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <label class="text-sm leading-none font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                            Width (tiles)
                        </label>
                        <input
                            v-model.number="width"
                            type="number"
                            min="1"
                            class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm file:border-0 file:bg-transparent file:text-sm file:font-medium focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        />
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm leading-none font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                            Height (tiles)
                        </label>
                        <input
                            v-model.number="height"
                            type="number"
                            min="1"
                            class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm file:border-0 file:bg-transparent file:text-sm file:font-medium focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        />
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-sm leading-none font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                        Tile Size (pixels)
                    </label>
                    <input
                        v-model.number="tileSize"
                        type="number"
                        min="1"
                        class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm file:border-0 file:bg-transparent file:text-sm file:font-medium focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                    />
                </div>

                <div v-if="error" class="text-sm text-red-500">
                    {{ error }}
                </div>
            </div>
        </div>
        <div class="flex items-center p-6 pt-0">
            <button
                @click="createMap"
                :disabled="isCreating"
                class="ring-offset-background focus-visible:ring-ring bg-primary text-primary-foreground hover:bg-primary/90 inline-flex h-10 items-center justify-center rounded-md px-4 py-2 text-sm font-medium transition-colors focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50"
            >
                <div v-if="isCreating" class="mr-2 h-4 w-4 animate-spin rounded-full border-b-2 border-white"></div>
                <span>{{ isCreating ? 'Creating...' : 'Create Map' }}</span>
            </button>
        </div>
    </div>
</template>
