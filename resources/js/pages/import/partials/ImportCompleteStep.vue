<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { api } from '@/lib/api';
import { AlertTriangle, CheckCircle, Download, Map, Palette } from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
    uploadedFiles: {
        files: Array<{ path: string; name: string; extension: string }>;
        mainMapFile: string | null;
        fieldTypeFile: string | null;
    } | null;
    parsedData: any;
    importConfig: {
        map_name: string;
        tileset_mappings: Record<string, string>;
        tileset_images: Record<string, File>;
        field_type_file_path: string | null;
    };
    isImporting: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'import-complete': [result: any];
    error: [message: string];
}>();

const isImporting = ref(false);
const importSuccess = ref(false);
const importError = ref<string | null>(null);

const performImport = async () => {
    if (!props.uploadedFiles?.mainMapFile || !props.parsedData) return;

    // Check if there are tilesets that need images but don't have them
    const tilesetsNeedingImages =
        props.parsedData.tilesets?.filter((t: any) => {
            const mapping = props.importConfig.tileset_mappings[t.original_name];
            const effectiveMapping = mapping || t.existing_tileset?.uuid;
            return effectiveMapping === 'create_new' && t.requires_upload && !props.importConfig.tileset_images[t.original_name];
        }) || [];

    if (tilesetsNeedingImages.length > 0) {
        const tilesetNames = tilesetsNeedingImages.map((t: any) => t.formatted_name).join(', ');
        emit('error', `Cannot import map: The following tilesets require image files to be uploaded: ${tilesetNames}`);
        return;
    }

    isImporting.value = true;
    importError.value = null;
    importSuccess.value = false;

    try {
        // Create FormData for the request
        const formData = new FormData();
        formData.append('file_path', props.uploadedFiles.mainMapFile);
        formData.append('format', props.parsedData.detected_format);
        formData.append('map_name', props.importConfig.map_name);
        formData.append('tileset_mappings', JSON.stringify(props.importConfig.tileset_mappings));

        // Add field type file path if available
        if (props.uploadedFiles.fieldTypeFile) {
            formData.append('field_type_file_path', props.uploadedFiles.fieldTypeFile);
        }

        // Add tileset images
        Object.entries(props.importConfig.tileset_images).forEach(([tilesetKey, file]) => {
            formData.append(`tileset_images[${tilesetKey}]`, file);
        });

        const response = await api.post('/map-import/complete', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        importSuccess.value = true;
        emit('import-complete', response.data);
    } catch (error: any) {
        const message = error.response?.data?.message || 'Failed to import map';
        importError.value = message;
        emit('error', message);
    } finally {
        isImporting.value = false;
    }
};

// Auto-start import when component mounts
performImport();
</script>

<template>
    <div class="space-y-6">
        <!-- Import Summary -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Map class="h-5 w-5" />
                    Import Summary
                </CardTitle>
                <CardDescription> Review the import configuration before proceeding. </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span class="text-sm font-medium">Map Name:</span>
                        <p class="text-muted-foreground text-sm">{{ importConfig.map_name }}</p>
                    </div>
                    <div>
                        <span class="text-sm font-medium">File Format:</span>
                        <p class="text-muted-foreground text-sm">{{ parsedData?.detected_format?.toUpperCase() }}</p>
                    </div>
                </div>

                <Separator />

                <div class="space-y-2">
                    <span class="text-sm font-medium">Tileset Mappings:</span>
                    <div class="space-y-1">
                        <div
                            v-for="(mapping, tilesetKey) in importConfig.tileset_mappings"
                            :key="tilesetKey"
                            class="flex items-center justify-between text-sm"
                        >
                            <span class="text-muted-foreground">
                                {{ parsedData?.tilesets?.find((t: any) => t.original_name === tilesetKey)?.formatted_name }}
                            </span>
                            <div class="flex items-center gap-2">
                                <Badge :variant="mapping === 'create_new' ? 'default' : 'secondary'">
                                    {{ mapping === 'create_new' ? 'Create New' : 'Use Existing' }}
                                </Badge>
                                <Badge v-if="mapping === 'create_new' && importConfig.tileset_images[tilesetKey]" variant="outline" class="text-xs">
                                    Image Uploaded
                                </Badge>
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Import Progress -->
        <Card v-if="isImporting">
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Download class="h-5 w-5" />
                    Importing Map
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div class="space-y-4">
                    <div class="flex items-center gap-2">
                        <div class="border-primary h-4 w-4 animate-spin rounded-full border-b-2"></div>
                        <span class="text-sm">Processing map data...</span>
                    </div>
                    <div class="text-muted-foreground space-y-2 text-sm">
                        <p>• Creating map structure</p>
                        <p>• Processing layers</p>
                        <p>• Mapping tilesets</p>
                        <p>• Finalizing import</p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Import Error -->
        <Card v-else-if="importError">
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-red-600">
                    <AlertTriangle class="h-5 w-5" />
                    Import Failed
                </CardTitle>
                <CardDescription> There was an error during the import process. </CardDescription>
            </CardHeader>
            <CardContent>
                <div class="space-y-4">
                    <div class="rounded-lg border border-red-200 bg-red-50 p-4">
                        <p class="text-red-800">{{ importError }}</p>
                    </div>
                    <div class="text-muted-foreground text-sm">
                        <p>Please check the error message above and try again. You can go back to the previous steps to fix any issues.</p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Import Complete -->
        <Card v-else-if="importSuccess">
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-green-600">
                    <CheckCircle class="h-5 w-5" />
                    Import Complete!
                </CardTitle>
                <CardDescription> Your map has been successfully imported. </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <Map class="h-4 w-4" />
                        <span class="font-medium">Map Details:</span>
                    </div>
                    <div class="space-y-1 pl-6 text-sm">
                        <div class="flex justify-between">
                            <span>Name:</span>
                            <span class="font-medium">{{ importConfig.map_name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Size:</span>
                            <span class="font-medium"> {{ parsedData?.map_info?.width }} × {{ parsedData?.map_info?.height }} tiles </span>
                        </div>
                        <div class="flex justify-between">
                            <span>Layers:</span>
                            <span class="font-medium">{{ parsedData?.layers?.length || 0 }}</span>
                        </div>
                    </div>
                </div>

                <Separator />

                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <Palette class="h-4 w-4" />
                        <span class="font-medium">Tilesets:</span>
                    </div>
                    <div class="space-y-1 pl-6 text-sm">
                        <div class="flex justify-between">
                            <span>New tilesets created:</span>
                            <span class="font-medium text-blue-600">
                                {{ Object.values(importConfig.tileset_mappings).filter((m) => m === 'create_new').length }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span>Existing tilesets used:</span>
                            <span class="font-medium text-green-600">
                                {{ Object.values(importConfig.tileset_mappings).filter((m) => m !== 'create_new').length }}
                            </span>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Action Buttons -->
        <div class="flex justify-center">
            <Button v-if="isImporting" disabled class="w-full">
                <div class="mr-2 h-4 w-4 animate-spin rounded-full border-b-2 border-white"></div>
                Importing...
            </Button>
            <Button v-else-if="importError" @click="performImport" class="w-full">
                <Download class="mr-2 h-4 w-4" />
                Retry Import
            </Button>
            <Button v-else-if="!importSuccess" @click="performImport" class="w-full">
                <Download class="mr-2 h-4 w-4" />
                Import Map
            </Button>
        </div>
    </div>
</template>
