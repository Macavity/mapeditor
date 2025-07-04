<template>
    <div class="tile-set-list">
        <div class="mb-4 flex justify-end">
            <Dialog v-model:open="isDialogOpen">
                <DialogTrigger as-child>
                    <Button variant="default">Import TileSet</Button>
                </DialogTrigger>
                <DialogContent>
                    <ImportTileSetDialog @success="isDialogOpen = false" />
                </DialogContent>
            </Dialog>
        </div>

        <div v-if="page.props.flash?.success" class="mb-4 border-l-4 border-green-500 bg-green-100 p-4 text-green-700">
            {{ page.props.flash.success }}
        </div>

        <div v-if="loading" class="flex items-center justify-center p-4">
            <div class="border-primary h-8 w-8 animate-spin rounded-full border-b-2"></div>
        </div>

        <div v-else-if="error" class="p-4 text-red-500">
            {{ error }}
        </div>

        <div v-else-if="store.tileSets.length === 0" class="p-4 text-gray-500">
            No tile sets found. Create your first tile set using the panel above.
        </div>

        <div v-else class="relative overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-sidebar-border/5 text-xs uppercase">
                    <tr>
                        <th scope="col" class="px-6 py-3">Name</th>
                        <th scope="col" class="px-6 py-3">Dimensions</th>
                        <th scope="col" class="px-6 py-3">Tile Size</th>
                        <th scope="col" class="px-6 py-3">Count</th>
                        <th scope="col" class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="tileSet in store.tileSets" :key="tileSet.uuid" class="border-sidebar-border/10 hover:bg-sidebar-border/5 border-b">
                        <td class="px-6 py-4 font-medium">{{ tileSet.name }}</td>
                        <td class="px-6 py-4 text-gray-500">{{ tileSet.imageWidth }}x{{ tileSet.imageHeight }}</td>
                        <td class="px-6 py-4 text-gray-500">{{ tileSet.tileWidth }}x{{ tileSet.tileHeight }}</td>
                        <td class="px-6 py-4 text-gray-500">{{ tileSet.tileCount }}</td>
                        <td class="px-6 py-4">
                            <div class="flex justify-end gap-2">
                                <Button variant="default" @click="editTileSet(tileSet.uuid)"> Edit </Button>
                                <Button variant="destructive" @click="deleteTileSet(tileSet.uuid)" :disabled="deleting === tileSet.uuid">
                                    <div v-if="deleting === tileSet.uuid" class="h-4 w-4 animate-spin rounded-full border-b-2 border-white"></div>
                                    <span v-else>Delete</span>
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogTrigger } from '@/components/ui/dialog';
import ImportTileSetDialog from '@/pages/manage-tilesets/partials/ImportTileSetDialog.vue';
import { useTileSetStore } from '@/stores/tileSetStore';
import type { PageProps } from '@/types/globals';
import { router, usePage } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

const page = usePage<PageProps>();
const store = useTileSetStore();
const loading = ref(false);
const error = ref<string | null>(null);
const deleting = ref<string | null>(null);
const isDialogOpen = ref(false);

const editTileSet = (uuid: string) => {
    router.visit(`/tile-sets/${uuid}/edit`);
};

const deleteTileSet = async (uuid: string) => {
    if (!confirm('Are you sure you want to delete this tile set?')) return;

    deleting.value = uuid;
    try {
        await store.deleteTileSet(uuid);
    } catch (e) {
        console.error(e);
        error.value = 'Failed to delete tile set';
    } finally {
        deleting.value = null;
    }
};

onMounted(async () => {
    if (!store.tileSets.length) {
        loading.value = true;
        try {
            await store.loadTileSets();
        } catch (e) {
            console.error(e);
            error.value = 'Failed to load tile sets';
        } finally {
            loading.value = false;
        }
    }
});
</script>

<style scoped>
.tileset-list .row {
    padding: 0.5rem 0;
    border-bottom: 1px solid #eee;
}

.tileset-list .row:hover {
    background-color: #f8f9fa;
}
</style>
