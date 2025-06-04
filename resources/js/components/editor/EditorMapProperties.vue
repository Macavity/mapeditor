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
        </div>
    </div>
</template>

<script setup lang="ts">
import type { TileMap } from '@/types/TileMap';

const props = defineProps<{
    map: TileMap;
}>();

const properties = [
    {
        field: 'Name',
        value: props.map.name,
    },
    {
        field: 'Author',
        value: props.map.creator?.name ?? 'Unknown',
        protected: true,
    },
    {
        field: 'Width',
        value: props.map.width,
    },
    {
        field: 'Height',
        value: props.map.height,
    },
    {
        field: 'Tile Height',
        value: props.map.tile_height,
    },
    {
        field: 'Tile Width',
        value: props.map.tile_width,
    },
] as Array<{ field: string; value: string | number; protected?: boolean }>;
</script>
