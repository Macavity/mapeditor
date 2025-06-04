<template>
    <form @submit="handleSubmit" class="space-y-6">
        <DialogHeader>
            <DialogTitle>Import TileSet</DialogTitle>
            <DialogDescription> Upload a tileset image and configure its properties. </DialogDescription>
        </DialogHeader>

        <div class="space-y-4">
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input id="name" v-model="form.name" type="text" required />
            </div>

            <div class="grid gap-2">
                <Label for="image">Image</Label>
                <Input id="image" ref="fileInput" type="file" accept="image/*" @change="handleFileChange" required />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="grid gap-2">
                    <Label for="tileWidth">Tile Width (px)</Label>
                    <Input id="tileWidth" v-model.number="form.tileWidth" type="number" min="1" required />
                </div>

                <div class="grid gap-2">
                    <Label for="tileHeight">Tile Height (px)</Label>
                    <Input id="tileHeight" v-model.number="form.tileHeight" type="number" min="1" required />
                </div>
            </div>

            <div v-if="error" class="text-sm text-red-500">
                {{ error }}
            </div>
        </div>

        <DialogFooter>
            <DialogClose asChild>
                <Button variant="secondary">Cancel</Button>
            </DialogClose>
            <Button type="submit" :disabled="submitting">
                <div v-if="submitting" class="h-4 w-4 animate-spin rounded-full border-b-2 border-white"></div>
                <span v-else>Import</span>
            </Button>
        </DialogFooter>
    </form>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { DialogClose, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTileSetStore } from '@/stores/tileSetStore';
import { ref } from 'vue';

const emit = defineEmits<{
    success: [];
}>();

const store = useTileSetStore();
const fileInput = ref<HTMLInputElement | null>(null);
const error = ref<string | null>(null);
const submitting = ref(false);

const form = ref({
    name: '',
    tileWidth: 32,
    tileHeight: 32,
    file: null as File | null,
});

const handleFileChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files[0]) {
        form.value.file = input.files[0];
    }
};

const handleSubmit = async (e: Event) => {
    e.preventDefault();

    if (!form.value.file) {
        error.value = 'Please select an image file';
        return;
    }

    submitting.value = true;
    error.value = null;

    try {
        const formData = new FormData();
        formData.append('name', form.value.name);
        formData.append('tileWidth', form.value.tileWidth.toString());
        formData.append('tileHeight', form.value.tileHeight.toString());
        formData.append('image', form.value.file);

        await store.importTileSet(formData);
        emit('success');
    } catch (e) {
        error.value = 'Failed to import tileset. Please try again.';
        return;
    } finally {
        submitting.value = false;
    }
};
</script>
