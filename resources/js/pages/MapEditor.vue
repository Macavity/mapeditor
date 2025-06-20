<script setup lang="ts">
import Editor from '@/components/editor/Editor.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { useEditorStore } from '@/stores/editorStore';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { AlertTriangle, ArrowLeft } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

const props = defineProps<{
    uuid: string;
}>();

const store = useEditorStore();
const error = ref<string | null>(null);
const isLoading = ref(true);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Maps',
        href: '/manage-maps',
    },
    {
        title: 'Map Editor',
        href: `/maps/${props.uuid}/edit`,
    },
];

const loadMap = async () => {
    try {
        await store.loadMap(props.uuid);
    } catch (err) {
        error.value = err instanceof Error ? err.message : 'An unexpected error occurred';
    } finally {
        isLoading.value = false;
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
            <!-- Loading State -->
            <div v-if="isLoading" class="flex h-full items-center justify-center">
                <div class="border-primary h-32 w-32 animate-spin rounded-full border-b-2"></div>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="flex h-full flex-col items-center justify-center gap-4">
                <div class="text-error-500 flex items-center gap-2">
                    <AlertTriangle class="h-6 w-6" />
                    <span class="text-xl">{{ error }}</span>
                </div>
                <Link href="/manage-maps" class="btn btn-primary flex items-center gap-2">
                    <ArrowLeft class="h-4 w-4" />
                    Return to Maps
                </Link>
            </div>

            <!-- Editor State -->
            <div v-else-if="store.mapMetadata" class="border-sidebar-border/70 dark:border-sidebar-border relative flex-1 rounded-xl border p-4">
                <Editor />
            </div>
        </div>
    </AppLayout>
</template>
