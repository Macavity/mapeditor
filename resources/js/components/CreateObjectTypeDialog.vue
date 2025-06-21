<template>
    <div class="create-object-type-dialog">
        <DialogHeader>
            <DialogTitle>Create Object Type</DialogTitle>
            <DialogDescription> Create a new object type that can be used in object layers. </DialogDescription>
        </DialogHeader>

        <form @submit.prevent="handleSubmit" class="space-y-4">
            <div class="space-y-2">
                <Label for="name">Name</Label>
                <Input id="name" v-model="form.name" type="text" placeholder="Enter object type name" :class="{ 'border-red-500': errors.name }" />
                <InputError v-if="errors.name" :message="errors.name" />
            </div>

            <div class="space-y-2">
                <Label for="color">Color</Label>
                <div class="flex items-center gap-2">
                    <Input id="color" v-model="form.color" type="color" class="h-10 w-20" />
                    <Input v-model="form.color" type="text" placeholder="#000000" :class="{ 'border-red-500': errors.color }" />
                </div>
                <InputError v-if="errors.color" :message="errors.color" />
            </div>

            <div class="space-y-2">
                <Label for="description">Description (Optional)</Label>
                <textarea
                    id="description"
                    v-model="form.description"
                    rows="3"
                    class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-[80px] w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                    placeholder="Enter description..."
                />
            </div>

            <div class="flex items-center space-x-2">
                <Checkbox id="is_solid" v-model:checked="form.is_solid" />
                <Label for="is_solid">Solid (blocks movement)</Label>
            </div>

            <div class="flex justify-end gap-2">
                <Button type="button" variant="outline" @click="$emit('cancel')"> Cancel </Button>
                <Button type="submit" :disabled="loading">
                    <div v-if="loading" class="mr-2 h-4 w-4 animate-spin rounded-full border-b-2 border-white"></div>
                    Create Object Type
                </Button>
            </div>
        </form>
    </div>
</template>

<script setup lang="ts">
import { Checkbox } from '@/components/ui/checkbox';
import { DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input, InputError } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ObjectTypeService } from '@/services/ObjectTypeService';
import { ref } from 'vue';

const emit = defineEmits<{
    success: [];
    cancel: [];
}>();

const loading = ref(false);
const errors = ref<{ name?: string; color?: string }>({});

const form = ref({
    name: '',
    color: '#000000',
    description: '',
    is_solid: true,
});

const handleSubmit = async () => {
    loading.value = true;
    errors.value = {};

    try {
        await ObjectTypeService.create({
            name: form.value.name,
            color: form.value.color,
            description: form.value.description || undefined,
            is_solid: form.value.is_solid,
        });

        emit('success');
    } catch (error: any) {
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
        } else {
            errors.value = { name: 'Failed to create object type' };
        }
    } finally {
        loading.value = false;
    }
};
</script>
