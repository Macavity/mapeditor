<script setup lang="ts">
import CreateMapPanel from '@/components/maps/CreateMapPanel.vue';
import MapList from '@/components/maps/MapList.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Maps',
        href: '/manage-maps',
    },
];
</script>

<template>
    <Head title="Manage Maps" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4">
                <CreateMapPanel />
            </div>

            <div class="border-sidebar-border/70 dark:border-sidebar-border flex-1 rounded-xl border p-4">
                <MapList />
            </div>
        </div>
    </AppLayout>
</template>
