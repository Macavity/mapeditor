<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogTrigger } from '@/components/ui/dialog';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CreateMapDialog from './partials/CreateMapDialog.vue';
import MapList from './partials/MapList.vue';

const page = usePage<SharedData>();
const isAdmin = computed(() => page.props.auth.user?.is_admin);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: isAdmin.value ? 'All Maps' : 'My Maps',
        href: '/manage-maps',
    },
];

const isDialogOpen = ref(false);

const openImportWizard = () => {
    router.visit('/import');
};
</script>

<template>
    <Head :title="isAdmin ? 'Manage All Maps' : 'My Maps'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-[calc(100vh-8rem)] flex-col gap-4 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">{{ isAdmin ? 'All Maps' : 'My Maps' }}</h1>
                    <p class="text-muted-foreground">
                        {{ isAdmin ? 'Manage all maps in the system' : 'Manage your created maps' }}
                    </p>
                </div>
                <div class="flex gap-2">
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
            </div>

            <div class="border-sidebar-border/70 dark:border-sidebar-border flex-1 rounded-xl border p-4">
                <MapList />
            </div>
        </div>
    </AppLayout>
</template>
