<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import TileSetList from '@/pages/manage-tilesets/partials/TileSetList.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tilesets',
        href: '/manage-tilesets',
    },
];
</script>

<template>
    <Head title="Manage Tilesets" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="border-sidebar-border/70 dark:border-sidebar-border flex-1 rounded-xl border p-4">
                <TileSetList />
            </div>
        </div>
    </AppLayout>
</template>
