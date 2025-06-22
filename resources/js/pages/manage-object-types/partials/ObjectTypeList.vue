<template>
    <div class="object-type-list">
        <div class="mb-4 flex justify-end">
            <Dialog v-model:open="isDialogOpen">
                <DialogTrigger as-child>
                    <Button variant="default">Create Object Type</Button>
                </DialogTrigger>
                <DialogContent>
                    <CreateObjectTypeDialog @success="isDialogOpen = false" />
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

        <div v-else-if="objectTypes.length === 0" class="p-4 text-gray-500">
            No object types found. Create your first object type using the button above.
        </div>

        <div v-else class="relative overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-sidebar-border/5 text-xs uppercase">
                    <tr>
                        <th scope="col" class="px-6 py-3">Name</th>
                        <th scope="col" class="px-6 py-3">Type</th>
                        <th scope="col" class="px-6 py-3">Color</th>
                        <th scope="col" class="px-6 py-3">Description</th>
                        <th scope="col" class="px-6 py-3">Solid</th>
                        <th scope="col" class="px-6 py-3">Created</th>
                        <th scope="col" class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="objectType in objectTypes" :key="objectType.id" class="border-sidebar-border/10 hover:bg-sidebar-border/5 border-b">
                        <td class="px-6 py-4 font-medium">{{ objectType.name }}</td>
                        <td class="px-6 py-4 text-gray-500">{{ objectType.type || 'No type' }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <div class="h-6 w-6 rounded border border-gray-300" :style="{ backgroundColor: objectType.color }"></div>
                                <span class="text-gray-500">{{ objectType.color }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-gray-500">
                            {{ objectType.description || 'No description' }}
                        </td>
                        <td class="px-6 py-4">
                            <span
                                :class="{
                                    'rounded px-2 py-1 text-xs font-medium': true,
                                    'bg-green-100 text-green-800': objectType.is_solid,
                                    'bg-gray-100 text-gray-800': !objectType.is_solid,
                                }"
                            >
                                {{ objectType.is_solid ? 'Yes' : 'No' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-500">{{ formatDate(objectType.created_at) }}</td>
                        <td class="px-6 py-4">
                            <div class="flex justify-end gap-2">
                                <Button variant="default" @click="editObjectType(objectType)">Edit</Button>
                                <Button variant="destructive" @click="deleteObjectType(objectType.id)" :disabled="deleting === objectType.id">
                                    <div v-if="deleting === objectType.id" class="h-4 w-4 animate-spin rounded-full border-b-2 border-white"></div>
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
import { ObjectTypeService, type ObjectType } from '@/services/ObjectTypeService';
import type { PageProps } from '@/types/globals';
import { usePage } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import CreateObjectTypeDialog from './CreateObjectTypeDialog.vue';

const page = usePage<PageProps>();
const loading = ref(false);
const error = ref<string | null>(null);
const deleting = ref<number | null>(null);
const isDialogOpen = ref(false);
const objectTypes = ref<ObjectType[]>([]);

const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleDateString();
};

const editObjectType = (objectType: ObjectType) => {
    // For now, we'll use a simple alert. In a real app, you might want a proper edit dialog
    const newName = prompt('Enter new name:', objectType.name);
    const newType = prompt('Enter new type (optional):', objectType.type);

    if (newName && (newName !== objectType.name || newType !== objectType.type)) {
        updateObjectType(objectType.id, {
            name: newName,
            type: newType || undefined,
            color: objectType.color,
            description: objectType.description || undefined,
            is_solid: objectType.is_solid,
        });
    }
};

const updateObjectType = async (id: number, data: { name: string; type?: string; color: string; description?: string; is_solid?: boolean }) => {
    try {
        await ObjectTypeService.update(id, {
            name: data.name,
            type: data.type,
            color: data.color,
            description: data.description || undefined,
            is_solid: data.is_solid,
        });
        await loadObjectTypes();
    } catch (e) {
        console.error(e);
        error.value = 'Failed to update object type';
    }
};

const deleteObjectType = async (id: number) => {
    if (!confirm('Are you sure you want to delete this object type?')) return;

    deleting.value = id;
    try {
        await ObjectTypeService.delete(id);
        await loadObjectTypes();
    } catch (e) {
        console.error(e);
        error.value = 'Failed to delete object type';
    } finally {
        deleting.value = null;
    }
};

const loadObjectTypes = async () => {
    loading.value = true;
    try {
        objectTypes.value = await ObjectTypeService.getAll();
    } catch (e) {
        console.error(e);
        error.value = 'Failed to load object types';
    } finally {
        loading.value = false;
    }
};

onMounted(async () => {
    await loadObjectTypes();
});
</script>

<style scoped>
.object-type-list .row {
    padding: 0.5rem 0;
    border-bottom: 1px solid #eee;
}

.object-type-list .row:hover {
    background-color: #f8f9fa;
}
</style>
