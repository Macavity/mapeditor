<script setup lang="ts">
import AppShell from '@/components/AppShell.vue';
import AppHeader from '@/layouts/app/partials/AppHeader.vue';
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
    <AppShell class="flex-col">
        <AppHeader :breadcrumbs="breadcrumbs" />
        <AppContent>
            <slot />
        </AppContent>
    </AppShell>
</template>
