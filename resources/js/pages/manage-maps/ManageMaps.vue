<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogTrigger } from '@/components/ui/dialog';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import CreateMapDialog from './partials/CreateMapDialog.vue';
import MapList from './partials/MapList.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Maps',
        href: '/manage-maps',
    },
];

const isDialogOpen = ref(false);

const openImportWizard = () => {
    router.visit('/manage-maps/import');
};
</script>

<template>
    <Head title="Manage Maps" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-[calc(100vh-8rem)] flex-col gap-4 rounded-xl p-4">
            <div class="flex justify-end gap-2">
                <Button variant="outline" @click="openImportWizard"> Import Wizard </Button>
                <Dialog v-model:open="isDialogOpen">
                    <DialogTrigger asChild>
                        <Button variant="default">New Map</Button>
                    </DialogTrigger>
                    <DialogContent>
                        <CreateMapDialog @success="isDialogOpen = false" />
                    </DialogContent>
                </Dialog>
            </div>

            <div class="border-sidebar-border/70 dark:border-sidebar-border flex-1 rounded-xl border p-4">
                <MapList />
            </div>
        </div>
    </AppLayout>
</template>
