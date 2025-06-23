<script setup lang="ts">
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Progress } from '@/components/ui/progress';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { AlertCircle, CheckCircle } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import FileUploadStep from './partials/FileUploadStep.vue';
import ImportCompleteStep from './partials/ImportCompleteStep.vue';
import MapOverviewStep from './partials/MapOverviewStep.vue';
import TilesetMappingStep from './partials/TilesetMappingStep.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Maps',
        href: '/manage-maps',
    },
    {
        title: 'Import Wizard',
        href: '/manage-maps/import',
    },
];

// Wizard state
const currentStep = ref(1);
const totalSteps = 4;

// File upload state
const uploadedFiles = ref<{
    files: Array<{ path: string; name: string; extension: string }>;
    mainMapFile: string | null;
    fieldTypeFile: string | null;
} | null>(null);

// Parsed data state
const parsedData = ref<{
    map_info: any;
    layers: any[];
    tilesets: any[];
    detected_format: string;
    suggested_tilesets: Record<string, any[]>;
} | null>(null);

// Import configuration state
const importConfig = ref({
    map_name: '',
    tileset_mappings: {} as Record<string, string>,
    tileset_images: {} as Record<string, File>,
    field_type_file_path: null as string | null,
});

// Import result state
const importResult = ref<{
    map: any;
    created_tilesets: any[];
} | null>(null);

// Loading states
const isUploading = ref(false);
const isParsing = ref(false);
const isImporting = ref(false);

// Error state
const error = ref<string | null>(null);

// Computed properties
const progress = computed(() => (currentStep.value / totalSteps) * 100);

const canProceedToNext = computed(() => {
    switch (currentStep.value) {
        case 1:
            return uploadedFiles.value !== null;
        case 2:
            return parsedData.value !== null && importConfig.value.map_name.trim() !== '';
        case 3:
            // Check if all tilesets are mapped and required images are uploaded
            if (!parsedData.value?.tilesets || Object.keys(importConfig.value.tileset_mappings).length === 0) {
                return false;
            }

            // Check if all tilesets that require uploads have images
            return parsedData.value.tilesets.every((tileset: any) => {
                const mapping = importConfig.value.tileset_mappings[tileset.original_name];

                // If creating new and requires upload, must have image
                if (mapping === 'create_new' && tileset.requires_upload) {
                    return importConfig.value.tileset_images[tileset.original_name];
                }

                return true;
            });
        default:
            return true;
    }
});

const canGoBack = computed(() => currentStep.value > 1);

// Methods
const handleFilesUploaded = (fileData: {
    files: Array<{ path: string; name: string; extension: string }>;
    mainMapFile: string | null;
    fieldTypeFile: string | null;
}) => {
    uploadedFiles.value = fileData;
    importConfig.value.field_type_file_path = fileData.fieldTypeFile;
    error.value = null;
    nextStep();
};

const handleFileParsed = (data: any) => {
    parsedData.value = data;
    importConfig.value.map_name = data.map_info.name || 'Imported Map';

    // Initialize tileset mappings based on existing tilesets
    const mappings: Record<string, string> = {};
    data.tilesets.forEach((tileset: any) => {
        if (tileset.existing_tileset?.uuid) {
            // Auto-map to existing tileset
            mappings[tileset.original_name] = tileset.existing_tileset.uuid;
        } else {
            // Default to create new for tilesets without existing matches
            mappings[tileset.original_name] = 'create_new';
        }
    });
    importConfig.value.tileset_mappings = mappings;

    error.value = null;
    nextStep();
};

const handleImportComplete = (result: any) => {
    importResult.value = result;
    error.value = null;
    nextStep();
};

const nextStep = () => {
    if (currentStep.value < totalSteps && canProceedToNext.value) {
        currentStep.value++;
    }
};

const previousStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};

const resetWizard = () => {
    currentStep.value = 1;
    uploadedFiles.value = null;
    parsedData.value = null;
    importConfig.value = {
        map_name: '',
        tileset_mappings: {},
        tileset_images: {},
        field_type_file_path: null,
    };
    importResult.value = null;
    error.value = null;
};

const goToMaps = () => {
    router.visit('/manage-maps');
};

const goToImportedMap = () => {
    if (importResult.value?.map?.uuid) {
        router.visit(`/maps/${importResult.value.map.uuid}/edit`);
    }
};

// Step components
const stepComponents: Record<number, any> = {
    1: FileUploadStep,
    2: MapOverviewStep,
    3: TilesetMappingStep,
    4: ImportCompleteStep,
};
</script>

<template>
    <Head title="Import Map Wizard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-[calc(100vh-8rem)] flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Import Map Wizard</h1>
                    <p class="text-muted-foreground">Step {{ currentStep }} of {{ totalSteps }}</p>
                </div>
                <Button variant="outline" @click="goToMaps"> Back to Maps </Button>
            </div>

            <!-- Progress Bar -->
            <div class="space-y-2">
                <div class="text-muted-foreground flex justify-between text-sm">
                    <span>Progress</span>
                    <span>{{ Math.round(progress) }}%</span>
                </div>
                <Progress :value="progress" class="h-2" />
            </div>

            <!-- Step Indicators -->
            <div class="flex items-center justify-center space-x-4">
                <div v-for="step in totalSteps" :key="step" class="flex items-center">
                    <div
                        :class="[
                            'flex h-8 w-8 items-center justify-center rounded-full border-2 text-sm font-medium',
                            step < currentStep
                                ? 'border-green-500 bg-green-500 text-white'
                                : step === currentStep
                                  ? 'border-primary bg-primary text-primary-foreground'
                                  : 'border-muted bg-muted text-muted-foreground',
                        ]"
                    >
                        <CheckCircle v-if="step < currentStep" class="h-4 w-4" />
                        <span v-else>{{ step }}</span>
                    </div>
                    <div v-if="step < totalSteps" :class="['h-0.5 w-8', step < currentStep ? 'bg-green-500' : 'bg-muted']" />
                </div>
            </div>

            <!-- Error Display -->
            <Alert v-if="error" variant="destructive">
                <AlertCircle class="h-4 w-4" />
                <AlertDescription>{{ error }}</AlertDescription>
            </Alert>

            <!-- Step Content -->
            <div class="flex-1 overflow-auto">
                <component
                    :is="stepComponents[currentStep]"
                    :uploaded-files="uploadedFiles"
                    :parsed-data="parsedData"
                    :import-config="importConfig"
                    :import-result="importResult"
                    :is-uploading="isUploading"
                    :is-parsing="isParsing"
                    :is-importing="isImporting"
                    @files-uploaded="handleFilesUploaded"
                    @file-parsed="handleFileParsed"
                    @import-complete="handleImportComplete"
                    @error="error = $event"
                    @update:import-config="importConfig = $event"
                />
            </div>

            <!-- Navigation -->
            <div class="flex justify-between">
                <Button v-if="canGoBack" variant="outline" @click="previousStep"> Previous </Button>
                <div v-else />

                <div class="flex gap-2">
                    <Button v-if="currentStep < totalSteps" variant="outline" @click="resetWizard"> Start Over </Button>
                    <Button v-if="currentStep === 3 && canProceedToNext" variant="default" @click="nextStep"> Proceed with Import </Button>
                    <Button v-if="currentStep === totalSteps && importResult" variant="default" @click="goToImportedMap"> Open Imported Map </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
