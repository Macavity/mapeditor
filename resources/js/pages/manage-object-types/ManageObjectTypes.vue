<script setup lang="ts">
import ObjectTypeList from '@/components/ObjectTypeList.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Object Types',
        href: '/manage-object-types',
    },
];
</script>

<template>
    <Head title="Manage Object Types" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="border-sidebar-border/70 dark:border-sidebar-border flex-1 rounded-xl border p-4">
                <ObjectTypeList />
            </div>
        </div>
    </AppLayout>
</template>
