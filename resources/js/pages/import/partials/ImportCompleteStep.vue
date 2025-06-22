<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { api } from '@/lib/api';
import { CheckCircle, Download, Map, Palette } from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
    uploadedFile: { path: string; name: string } | null;
    parsedData: any;
    importConfig: {
        map_name: string;
        tileset_mappings: Record<string, string>;
        preserve_uuid: boolean;
    };
    isImporting: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'import-complete': [result: any];
    error: [message: string];
}>();

const isImporting = ref(false);

const performImport = async () => {
    if (!props.uploadedFile || !props.parsedData) return;

    isImporting.value = true;

    try {
        const response = await api.post('/map-import/complete', {
            file_path: props.uploadedFile.path,
            format: props.parsedData.detected_format,
            map_name: props.importConfig.map_name,
            tileset_mappings: props.importConfig.tileset_mappings,
            preserve_uuid: props.importConfig.preserve_uuid,
        });

        emit('import-complete', response.data);
    } catch (error: any) {
        const message = error.response?.data?.message || 'Failed to import map';
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
                            v-for="(mapping, importedUuid) in importConfig.tileset_mappings"
                            :key="importedUuid"
                            class="flex items-center justify-between text-sm"
                        >
                            <span class="text-muted-foreground">
                                {{ parsedData?.tilesets?.find((t) => t.uuid === importedUuid)?.name }}
                            </span>
                            <Badge :variant="mapping === 'create_new' ? 'default' : 'secondary'">
                                {{ mapping === 'create_new' ? 'Create New' : 'Use Existing' }}
                            </Badge>
                        </div>
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    <Checkbox :checked="importConfig.preserve_uuid" disabled />
                    <span class="text-muted-foreground text-sm"> Preserve original UUIDs: {{ importConfig.preserve_uuid ? 'Yes' : 'No' }} </span>
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

        <!-- Import Complete -->
        <Card v-else>
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
            <Button v-else @click="performImport" class="w-full">
                <Download class="mr-2 h-4 w-4" />
                Import Map
            </Button>
        </div>
    </div>
</template>
