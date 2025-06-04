<script setup lang="ts">
import Editor from '@/components/editor/Editor.vue';
import type { MapDto } from '@/dtos/Map.dto';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

const props = defineProps<{
    uuid: string;
}>();

const map = ref<MapDto | null>(null);
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Map Editor',
        href: `/maps/${props.uuid}/edit`,
    },
];

const loadMap = async () => {
    try {
        const response = await fetch(`/api/tile-maps/${props.uuid}`);
        if (!response.ok) {
            throw new Error('Failed to load map');
        }
        map.value = await response.json();
    } catch (error) {
        console.error('Error loading map:', error);
        router.visit('/dashboard');
    }
};

onMounted(() => {
    loadMap();
});
</script>

<template>
    <Head title="Map Editor" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div v-if="map" class="border-sidebar-border/70 dark:border-sidebar-border relative flex-1 rounded-xl border">
                <Editor :map="map" />
            </div>
            <div v-else class="flex h-full items-center justify-center">
                <div class="border-primary h-32 w-32 animate-spin rounded-full border-b-2"></div>
            </div>
        </div>
    </AppLayout>
</template>
