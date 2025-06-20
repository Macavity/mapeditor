<template>
    <div class="field-type-list">
        <div class="mb-4 flex justify-end">
            <Dialog v-model:open="isDialogOpen">
                <DialogTrigger as-child>
                    <Button variant="default">Create Field Type</Button>
                </DialogTrigger>
                <DialogContent>
                    <CreateFieldTypeDialog @success="isDialogOpen = false" />
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

        <div v-else-if="fieldTypes.length === 0" class="p-4 text-gray-500">
            No field types found. Create your first field type using the button above.
        </div>

        <div v-else class="relative overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-sidebar-border/5 text-xs uppercase">
                    <tr>
                        <th scope="col" class="px-6 py-3">Name</th>
                        <th scope="col" class="px-6 py-3">Color</th>
                        <th scope="col" class="px-6 py-3">Created</th>
                        <th scope="col" class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="fieldType in fieldTypes" :key="fieldType.id" class="border-sidebar-border/10 hover:bg-sidebar-border/5 border-b">
                        <td class="px-6 py-4 font-medium">{{ fieldType.name }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <div class="h-6 w-6 rounded border border-gray-300" :style="{ backgroundColor: fieldType.color }"></div>
                                <span class="text-gray-500">{{ fieldType.color }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-gray-500">{{ formatDate(fieldType.created_at) }}</td>
                        <td class="px-6 py-4">
                            <div class="flex justify-end gap-2">
                                <Button variant="default" @click="editFieldType(fieldType)">Edit</Button>
                                <Button variant="destructive" @click="deleteFieldType(fieldType.id)" :disabled="deleting === fieldType.id">
                                    <div v-if="deleting === fieldType.id" class="h-4 w-4 animate-spin rounded-full border-b-2 border-white"></div>
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
import CreateFieldTypeDialog from '@/components/CreateFieldTypeDialog.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogTrigger } from '@/components/ui/dialog';
import { FieldTypeService, type FieldType } from '@/services/FieldTypeService';
import type { PageProps } from '@/types/globals';
import { usePage } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

const page = usePage<PageProps>();
const loading = ref(false);
const error = ref<string | null>(null);
const deleting = ref<number | null>(null);
const isDialogOpen = ref(false);
const fieldTypes = ref<FieldType[]>([]);

const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleDateString();
};

const editFieldType = (fieldType: FieldType) => {
    // For now, we'll use a simple alert. In a real app, you might want a proper edit dialog
    const newName = prompt('Enter new name:', fieldType.name);
    if (newName && newName !== fieldType.name) {
        updateFieldType(fieldType.id, { ...fieldType, name: newName });
    }
};

const updateFieldType = async (id: number, data: { name: string; color: string }) => {
    try {
        await FieldTypeService.update(id, data);
        await loadFieldTypes();
    } catch (e) {
        console.error(e);
        error.value = 'Failed to update field type';
    }
};

const deleteFieldType = async (id: number) => {
    if (!confirm('Are you sure you want to delete this field type?')) return;

    deleting.value = id;
    try {
        await FieldTypeService.delete(id);
        await loadFieldTypes();
    } catch (e) {
        console.error(e);
        error.value = 'Failed to delete field type';
    } finally {
        deleting.value = null;
    }
};

const loadFieldTypes = async () => {
    loading.value = true;
    try {
        fieldTypes.value = await FieldTypeService.getAll();
    } catch (e) {
        console.error(e);
        error.value = 'Failed to load field types';
    } finally {
        loading.value = false;
    }
};

onMounted(async () => {
    await loadFieldTypes();
});
</script>

<style scoped>
.field-type-list .row {
    padding: 0.5rem 0;
    border-bottom: 1px solid #eee;
}

.field-type-list .row:hover {
    background-color: #f8f9fa;
}
</style>
