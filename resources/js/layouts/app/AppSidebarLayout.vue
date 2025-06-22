<script setup lang="ts">
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/layouts/app/partials/AppSidebar.vue';
import AppSidebarHeader from '@/layouts/app/partials/AppSidebarHeader.vue';
import AppContent from '@/layouts/partials/AppContent.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
    </AppShell>
</template>
