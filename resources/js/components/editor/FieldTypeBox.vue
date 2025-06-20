<script setup lang="ts">
import { useEditorStore } from '@/stores/editorStore';
import { computed, onMounted } from 'vue';

const editorStore = useEditorStore();

const fieldTypesLoaded = computed(() => editorStore.fieldTypes.length > 0);

onMounted(async () => {
    try {
        await editorStore.loadFieldTypes();
    } catch (error) {
        console.error('Failed to load field types:', error);
    }
});

const selectFieldType = (fieldType: any) => {
    editorStore.selectFieldType(fieldType);
};
</script>

<template>
    <div class="flex h-full max-h-full flex-col rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <!-- Header -->
        <div class="border-b border-gray-200 p-4 dark:border-gray-700">
            <h3 class="text-lg font-semibold">Field Types</h3>
        </div>

        <!-- Body -->
        <div class="flex flex-1 flex-col gap-4 overflow-hidden p-4">
            <!-- Loading State -->
            <div v-if="!fieldTypesLoaded" class="flex items-center justify-center py-8">
                <div class="h-6 w-6 animate-spin rounded-full border-2 border-blue-600 border-t-transparent"></div>
            </div>

            <!-- Field Types List -->
            <div v-else-if="editorStore.fieldTypes.length > 0" class="flex-1 overflow-y-auto">
                <div class="space-y-2">
                    <div
                        v-for="fieldType in editorStore.fieldTypes"
                        :key="fieldType.id"
                        @click="selectFieldType(fieldType)"
                        class="flex cursor-pointer items-center gap-3 rounded-lg border p-3 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700"
                        :class="{
                            'border-blue-500 bg-blue-50 dark:bg-blue-900/20': editorStore.selectedFieldType?.id === fieldType.id,
                            'border-gray-200 dark:border-gray-600': editorStore.selectedFieldType?.id !== fieldType.id,
                        }"
                    >
                        <!-- Color indicator -->
                        <div class="h-6 w-6 rounded border border-gray-300 dark:border-gray-600" :style="{ backgroundColor: fieldType.color }"></div>

                        <!-- Field type name -->
                        <span class="flex-1 text-sm font-medium">{{ fieldType.name }}</span>

                        <!-- Selection indicator -->
                        <div v-if="editorStore.selectedFieldType?.id === fieldType.id" class="h-2 w-2 rounded-full bg-blue-600"></div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="flex flex-1 items-center justify-center py-8">
                <div class="text-center text-gray-500 dark:text-gray-400">
                    <p class="text-sm">No field types available</p>
                    <p class="text-xs">Create field types in the Field Types management page</p>
                </div>
            </div>

            <!-- Selected Field Type Info -->
            <div v-if="editorStore.selectedFieldType" class="border-t border-gray-200 pt-4 dark:border-gray-700">
                <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-700">
                    <div class="flex items-center gap-2">
                        <div
                            class="h-4 w-4 rounded border border-gray-300 dark:border-gray-600"
                            :style="{ backgroundColor: editorStore.selectedFieldType.color }"
                        ></div>
                        <span class="text-sm font-medium">{{ editorStore.selectedFieldType.name }}</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">Click on the map to place this field type</p>
                </div>
            </div>
        </div>
    </div>
</template>
