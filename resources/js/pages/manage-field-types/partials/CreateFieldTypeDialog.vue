<template>
    <div class="create-field-type-dialog">
        <DialogHeader>
            <DialogTitle>Create Field Type</DialogTitle>
            <DialogDescription> Create a new field type with a name and color. </DialogDescription>
        </DialogHeader>

        <form @submit.prevent="handleSubmit" class="space-y-4">
            <div class="space-y-2">
                <Label for="name">Name</Label>
                <Input id="name" v-model="form.name" type="text" placeholder="Enter field type name" required :disabled="loading" />
                <InputError v-if="errors.name" :message="errors.name" />
            </div>

            <div class="space-y-2">
                <Label for="color">Color</Label>
                <div class="flex gap-2">
                    <Input id="color" v-model="form.color" type="color" required :disabled="loading" class="h-10 w-16 p-1" />
                    <Input
                        v-model="form.color"
                        type="text"
                        placeholder="#FF0000"
                        pattern="^#[0-9A-Fa-f]{6}$"
                        required
                        :disabled="loading"
                        class="flex-1"
                    />
                </div>
                <InputError v-if="errors.color" :message="errors.color" />
            </div>

            <div class="flex justify-end gap-2">
                <Button type="button" variant="outline" @click="$emit('cancel')" :disabled="loading"> Cancel </Button>
                <Button type="submit" :disabled="loading">
                    <div v-if="loading" class="mr-2 h-4 w-4 animate-spin rounded-full border-b-2 border-white"></div>
                    Create Field Type
                </Button>
            </div>
        </form>
    </div>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input, InputError } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { FieldTypeService } from '@/services/FieldTypeService';
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
});

const handleSubmit = async () => {
    loading.value = true;
    errors.value = {};

    try {
        await FieldTypeService.create({
            name: form.value.name,
            color: form.value.color,
        });

        emit('success');
    } catch (error: any) {
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
        } else {
            errors.value = { name: 'Failed to create field type' };
        }
    } finally {
        loading.value = false;
    }
};
</script>
