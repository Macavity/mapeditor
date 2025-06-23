<template>
    <div class="map-list">
        <div v-if="store.loading && !deletingMap" :data-testid="TestId.MAP_LIST_LOADING" class="flex items-center justify-center p-4">
            <div class="border-primary h-8 w-8 animate-spin rounded-full border-b-2"></div>
        </div>

        <div v-else-if="store.error" :data-testid="TestId.MAP_LIST_ERROR" class="p-4 text-red-500">
            {{ store.error }}
        </div>

        <div v-else-if="store.maps.length === 0" :data-testid="TestId.MAP_LIST_EMPTY_STATE" class="p-4 text-gray-500">
            No maps found. Create your first map using the panel above.
        </div>

        <div v-else class="relative overflow-x-auto">
            <table :data-testid="TestId.MAP_LIST_TABLE" class="w-full text-left text-sm">
                <thead class="bg-sidebar-border/5 text-xs uppercase">
                    <tr>
                        <th scope="col" class="px-6 py-3">Name</th>
                        <th scope="col" class="px-6 py-3">Dimensions</th>
                        <th v-if="isAdmin" scope="col" class="px-6 py-3">Creator</th>
                        <th scope="col" class="px-6 py-3">Created</th>
                        <th scope="col" class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="map in store.maps"
                        :key="map.uuid"
                        :data-testid="`${TestId.MAP_ROW}-${map.uuid}`"
                        class="border-sidebar-border/10 hover:bg-sidebar-border/5 border-b"
                    >
                        <td class="px-6 py-4 font-medium" :data-testid="`${TestId.MAP_ROW_NAME}`">
                            {{ map.name }}
                        </td>
                        <td class="px-6 py-4 text-gray-500">{{ map.width }}x{{ map.height }}</td>
                        <td v-if="isAdmin" class="px-6 py-4 text-gray-500">
                            {{ map.external_creator ?? map.creator?.name ?? 'Unknown' }}
                        </td>
                        <td class="px-6 py-4 text-gray-500">{{ new Date(map.created_at).toLocaleDateString() }}</td>
                        <td class="px-6 py-4">
                            <div class="flex justify-end gap-2">
                                <button
                                    type="button"
                                    :data-testid="`${TestId.MAP_ROW_TEST_BUTTON}-${map.uuid}`"
                                    @click="goToMapTest(map.uuid)"
                                    class="ring-offset-background focus-visible:ring-ring bg-secondary text-secondary-foreground hover:bg-secondary/80 inline-flex h-9 items-center justify-center rounded-md px-3 py-2 text-sm font-medium transition-colors focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50"
                                >
                                    Test
                                </button>
                                <button
                                    v-if="canEditMap(map)"
                                    type="button"
                                    :data-testid="`${TestId.MAP_ROW_EDIT_BUTTON}-${map.uuid}`"
                                    @click="goToMapEdit(map.uuid)"
                                    class="ring-offset-background focus-visible:ring-ring bg-primary text-primary-foreground hover:bg-primary/90 inline-flex h-9 items-center justify-center rounded-md px-3 py-2 text-sm font-medium transition-colors focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50"
                                >
                                    Edit
                                </button>
                                <button
                                    v-if="canDeleteMap(map)"
                                    type="button"
                                    :data-testid="`${TestId.MAP_ROW_DELETE_BUTTON}-${map.uuid}`"
                                    @click="deleteMap(map.uuid)"
                                    :disabled="deletingMap === map.uuid"
                                    class="ring-offset-background focus-visible:ring-ring bg-destructive text-destructive-foreground hover:bg-destructive/90 inline-flex h-9 items-center justify-center rounded-md px-3 py-2 text-sm font-medium transition-colors focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50"
                                >
                                    <div v-if="deletingMap === map.uuid" class="h-4 w-4 animate-spin rounded-full border-b-2 border-white"></div>
                                    <span v-else>Delete</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useMapStore } from '@/stores/mapStore';
import { type SharedData } from '@/types';
import { TestId } from '@/types/TestId';
import { type TileMap } from '@/types/TileMap';

import { router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

const page = usePage<SharedData>();
const store = useMapStore();
const deletingMap = ref<string | null>(null);

const isAdmin = computed(() => page.props.auth.user?.is_admin);
const currentUserId = computed(() => page.props.auth.user?.id);

const canEditMap = (map: TileMap): boolean => {
    return isAdmin.value || map.creator?.id === currentUserId.value;
};

const canDeleteMap = (map: TileMap): boolean => {
    return isAdmin.value || map.creator?.id === currentUserId.value;
};

const goToMapEdit = (uuid: string) => {
    router.visit(`/maps/${uuid}/edit`);
};

const goToMapTest = (uuid: string) => {
    router.visit(`/maps/${uuid}/test`);
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
