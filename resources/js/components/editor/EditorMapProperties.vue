<template>
    <div class="flex flex-col gap-4">
        <div class="border-sidebar-border/70 border-b pb-4">
            <h2 class="text-lg font-semibold">Properties</h2>
        </div>

        <div class="flex flex-col gap-4">
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

            <!-- Action Buttons - Temporarily hidden
            <div class="flex gap-2">
                <button class="border-primary text-primary hover:bg-primary rounded-lg border px-3 py-1 transition-colors hover:text-white">
                    <Plus class="h-4 w-4" />
                </button>
                <button class="border-error-500 text-error-500 hover:bg-error-500 rounded-lg border px-3 py-1 transition-colors hover:text-white">
                    <Minus class="h-4 w-4" />
                </button>
            </div>
            -->
        </div>
    </div>
</template>

<script setup lang="ts">
import { useEditorStore } from '@/stores/editorStore';

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
