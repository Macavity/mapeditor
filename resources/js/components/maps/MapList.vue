<template>
    <div class="map-list">
        <div v-if="store.loading && !deletingMap" class="flex items-center justify-center p-4">
            <div class="border-primary h-8 w-8 animate-spin rounded-full border-b-2"></div>
        </div>

        <div v-else-if="store.error" class="p-4 text-red-500">
            {{ store.error }}
        </div>

        <div v-else-if="store.maps.length === 0" class="p-4 text-gray-500">No maps found. Create your first map using the panel above.</div>

        <div v-else class="divide-sidebar-border/70 divide-y">
            <div class="grid grid-cols-[1fr,auto] gap-4 p-4 font-medium">
                <div>Name</div>
                <div>Actions</div>
            </div>

            <div v-for="map in store.maps" :key="map.uuid" class="hover:bg-sidebar-border/5 grid grid-cols-[1fr,auto] gap-4 p-4">
                <div class="flex items-center">
                    {{ map.name }}
                    <span class="ml-2 text-sm text-gray-500"> {{ map.width }}x{{ map.height }} </span>
                </div>
                <div class="flex gap-2">
                    <button
                        type="button"
                        @click="goToMapEdit(map.uuid)"
                        class="ring-offset-background focus-visible:ring-ring bg-primary text-primary-foreground hover:bg-primary/90 inline-flex h-10 items-center justify-center rounded-md px-4 py-2 text-sm font-medium transition-colors focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50"
                    >
                        Edit
                    </button>
                    <button
                        type="button"
                        @click="deleteMap(map.uuid)"
                        :disabled="deletingMap === map.uuid"
                        class="ring-offset-background focus-visible:ring-ring bg-destructive text-destructive-foreground hover:bg-destructive/90 inline-flex h-10 items-center justify-center rounded-md px-4 py-2 text-sm font-medium transition-colors focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50"
                    >
                        <div v-if="deletingMap === map.uuid" class="h-4 w-4 animate-spin rounded-full border-b-2 border-white"></div>
                        <span v-else>Delete</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useMapStore } from '@/stores/mapStore';
import { router } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

const store = useMapStore();
const deletingMap = ref<string | null>(null);

const goToMapEdit = (uuid: string) => {
    router.visit(`/maps/${uuid}/edit`);
};

const deleteMap = async (uuid: string) => {
    if (!confirm('Are you sure you want to delete this map?')) return;

    deletingMap.value = uuid;
    try {
        await store.deleteMap(uuid);
    } finally {
        deletingMap.value = null;
    }
};

onMounted(() => {
    if (!store.loaded) {
        store.loadMaps();
    }
});
</script>
