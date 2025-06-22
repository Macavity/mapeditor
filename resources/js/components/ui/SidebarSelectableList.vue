<script setup lang="ts">
import { computed } from 'vue';

interface SelectableItem {
    id: number | string;
    name: string;
    color: string;
    type?: string;
    [key: string]: any; // Allow additional properties
}

interface Props {
    title: string;
    items: SelectableItem[];
    selectedItem: SelectableItem | null;
    loading?: boolean;
    emptyMessage?: string;
    emptySubMessage?: string;
    showSelectedInfo?: boolean;
    selectedInfoMessage?: string;
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
    emptyMessage: 'No items available',
    emptySubMessage: '',
    showSelectedInfo: true,
    selectedInfoMessage: 'Click on the map to place this item',
});

const emit = defineEmits<{
    select: [item: SelectableItem];
}>();

const hasItems = computed(() => props.items.length > 0);

const selectItem = (item: SelectableItem) => {
    emit('select', item);
};
</script>

<template>
    <div class="flex h-full max-h-full flex-col rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <!-- Header -->
        <div class="border-b border-gray-200 p-4 dark:border-gray-700">
            <h3 class="text-lg font-semibold">{{ title }}</h3>
        </div>

        <!-- Body -->
        <div class="flex flex-1 flex-col gap-4 overflow-hidden p-4">
            <!-- Loading State -->
            <div v-if="loading" class="flex items-center justify-center py-8">
                <div class="h-6 w-6 animate-spin rounded-full border-2 border-blue-600 border-t-transparent"></div>
            </div>

            <!-- Items List -->
            <div v-else-if="hasItems" class="flex-1 overflow-y-auto">
                <div class="space-y-2">
                    <div
                        v-for="item in items"
                        :key="item.id"
                        @click="selectItem(item)"
                        class="flex cursor-pointer items-center gap-3 rounded-lg border p-3 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700"
                        :class="{
                            'border-blue-500 bg-blue-50 dark:bg-blue-900/20': selectedItem?.id === item.id,
                            'border-gray-200 dark:border-gray-600': selectedItem?.id !== item.id,
                        }"
                    >
                        <!-- Color indicator -->
                        <div 
                            class="h-6 w-6 rounded border border-gray-300 dark:border-gray-600" 
                            :style="{ backgroundColor: item.color }"
                        ></div>

                        <!-- Item name -->
                        <div class="flex-1">
                            <span class="text-sm font-medium">{{ item.name }}</span>
                            <div v-if="item.type" class="text-xs text-gray-500 dark:text-gray-400">{{ item.type }}</div>
                        </div>

                        <!-- Selection indicator -->
                        <div 
                            v-if="selectedItem?.id === item.id" 
                            class="h-2 w-2 rounded-full bg-blue-600"
                        ></div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="flex flex-1 items-center justify-center py-8">
                <div class="text-center text-gray-500 dark:text-gray-400">
                    <p class="text-sm">{{ emptyMessage }}</p>
                    <p v-if="emptySubMessage" class="text-xs">{{ emptySubMessage }}</p>
                </div>
            </div>

            <!-- Selected Item Info -->
            <div 
                v-if="showSelectedInfo && selectedItem" 
                class="border-t border-gray-200 pt-4 dark:border-gray-700"
            >
                <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-700">
                    <div class="flex items-center gap-2">
                        <div
                            class="h-4 w-4 rounded border border-gray-300 dark:border-gray-600"
                            :style="{ backgroundColor: selectedItem.color }"
                        ></div>
                        <div>
                            <span class="text-sm font-medium">{{ selectedItem.name }}</span>
                            <div v-if="selectedItem.type" class="text-xs text-gray-600 dark:text-gray-400">{{ selectedItem.type }}</div>
                        </div>
                    </div>
                    <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">{{ selectedInfoMessage }}</p>
                </div>
            </div>
        </div>
    </div>
</template> 