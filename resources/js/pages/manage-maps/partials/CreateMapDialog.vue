<template>
    <form @submit.prevent="createMap" class="space-y-6" :data-testid="TestId.CREATE_MAP_FORM">
        <DialogHeader>
            <DialogTitle>Create New Map</DialogTitle>
            <DialogDescription>Create a new map with custom dimensions.</DialogDescription>
        </DialogHeader>

        <div class="space-y-4">
            <div class="space-y-2">
                <Label>Name</Label>
                <Input v-model="name" type="text" placeholder="Enter map name" required :data-testid="TestId.MAP_NAME_INPUT" />
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2">
                    <Label>Width (tiles)</Label>
                    <Input v-model.number="width" type="number" min="1" required :data-testid="TestId.MAP_WIDTH_INPUT" />
                </div>

                <div class="space-y-2">
                    <Label>Height (tiles)</Label>
                    <Input v-model.number="height" type="number" min="1" required :data-testid="TestId.MAP_HEIGHT_INPUT" />
                </div>
            </div>

            <div class="space-y-2">
                <Label>Tile Size (pixels)</Label>
                <Input v-model.number="tileSize" type="number" min="1" required :data-testid="TestId.MAP_TILE_SIZE_INPUT" />
            </div>

            <div v-if="error" class="text-sm text-red-500">
                {{ error }}
            </div>
        </div>

        <DialogFooter>
            <DialogClose asChild>
                <Button variant="secondary">Cancel</Button>
            </DialogClose>
            <Button type="submit" :disabled="isCreating" :data-testid="TestId.CREATE_MAP_SUBMIT">
                <div v-if="isCreating" class="h-4 w-4 animate-spin rounded-full border-b-2 border-white"></div>
                <span v-else>Create Map</span>
            </Button>
        </DialogFooter>
    </form>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { DialogClose, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { TestId } from '@/types/TestId';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useMapStore } from '@/stores/mapStore';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

const emit = defineEmits<{
    success: [];
}>();

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

        emit('success');
        router.visit(`/maps/${newMap.uuid}/edit`);
    } catch (e) {
        error.value = e instanceof Error ? e.message : 'Failed to create map';
    } finally {
        isCreating.value = false;
    }
}
</script>
