<template>
    <div class="flex flex-col gap-4 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <div class="border-b border-gray-200 p-4 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <List class="h-5 w-5" />
                    <h3 class="text-lg font-semibold">Properties</h3>
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-4 p-2">
            <!-- Properties Form -->
            <div class="flex flex-col gap-3">
                <div v-for="(prop, i) in properties" :key="i" class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ prop.field }}
                    </label>
                    <input
                        type="text"
                        :value="prop.value"
                        class="focus:border-primary focus:ring-primary rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-1 focus:outline-none disabled:bg-gray-100 disabled:text-gray-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:disabled:bg-gray-900"
                        :disabled="prop.protected"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useEditorStore } from '@/stores/editorStore';
import { List } from 'lucide-vue-next';

const store = useEditorStore();

const properties = [
    {
        field: 'Name',
        value: store.mapMetadata.name,
    },
    {
        field: 'Author',
        value: store.mapMetadata.creatorName ?? 'Unknown',
        protected: true,
    },
    {
        field: 'Width',
        value: store.mapMetadata.width,
    },
    {
        field: 'Height',
        value: store.mapMetadata.height,
    },
    {
        field: 'Tile Height',
        value: store.mapMetadata.tileHeight,
    },
    {
        field: 'Tile Width',
        value: store.mapMetadata.tileWidth,
    },
] as Array<{ field: string; value: string | number; protected?: boolean }>;
</script>
