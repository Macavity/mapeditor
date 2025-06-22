<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import FieldTypeList from './partials/FieldTypeList.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Field Types',
        href: '/manage-field-types',
    },
];
</script>

<template>
    <Head title="Manage Field Types" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="border-sidebar-border/70 dark:border-sidebar-border flex-1 rounded-xl border p-4">
                <FieldTypeList />
            </div>
        </div>
    </AppLayout>
</template>
