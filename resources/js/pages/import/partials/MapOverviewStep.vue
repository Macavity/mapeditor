<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { api } from '@/lib/api';
import { AlertTriangle, FileText, Info, Layers, Map, Palette } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface Props {
    uploadedFiles: {
        files: Array<{ path: string; name: string; extension: string }>;
        mainMapFile: string | null;
        fieldTypeFile: string | null;
    } | null;
    isParsing: boolean;
    importConfig: {
        map_name: string;
        tileset_mappings: Record<string, string>;
        field_type_file_path: string | null;
    };
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'file-parsed': [data: any];
    error: [message: string];
    'update:import-config': [config: any];
}>();

const parsedData = ref<any>(null);
const isParsing = ref(false);

const updateImportConfig = (updates: Partial<typeof props.importConfig>) => {
    emit('update:import-config', { ...props.importConfig, ...updates });
};

const parseFile = async () => {
    if (!props.uploadedFiles?.mainMapFile) return;

    isParsing.value = true;

    try {
        const requestData: any = {
            file_path: props.uploadedFiles.mainMapFile,
        };

        // Add field type file path if available
        if (props.uploadedFiles.fieldTypeFile) {
            requestData.field_type_file_path = props.uploadedFiles.fieldTypeFile;
        }

        const response = await api.post('/map-import/parse', requestData);

        parsedData.value = response.data;
        emit('file-parsed', response.data);
    } catch (error: any) {
        const message = error.response?.data?.message || 'Failed to parse file';
        emit('error', message);
    } finally {
        isParsing.value = false;
    }
};

// Auto-parse when component mounts
watch(
    () => props.uploadedFiles,
    (files) => {
        if (files?.mainMapFile && !parsedData.value) {
            parseFile();
        }
    },
    { immediate: true },
);

const getLayerTypeIcon = (type: string) => {
    switch (type) {
        case 'background':
            return '🏞️';
        case 'floor':
            return '🏠';
        case 'object':
            return '🎯';
        case 'field_type':
            return '🌱';
        default:
            return '📄';
    }
};

const getLayerTypeColor = (type: string) => {
    switch (type) {
        case 'background':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
        case 'floor':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        case 'object':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200';
        case 'field_type':
            return 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
    }
};
</script>

<template>
    <div class="space-y-6">
        <!-- File Information -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <FileText class="h-5 w-5" />
                    File Information
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium">Main Map File:</span>
                        <span class="text-muted-foreground text-sm">{{
                            uploadedFiles?.files.find((f) => f.path === uploadedFiles?.mainMapFile)?.name
                        }}</span>
                    </div>
                    <div v-if="uploadedFiles?.fieldTypeFile" class="flex items-center justify-between">
                        <span class="text-sm font-medium">Field Type File:</span>
                        <span class="text-muted-foreground text-sm">{{
                            uploadedFiles?.files.find((f) => f.path === uploadedFiles?.fieldTypeFile)?.name
                        }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium">Detected Format:</span>
                        <Badge v-if="parsedData?.detected_format" variant="secondary">
                            {{ parsedData.detected_format.toUpperCase() }}
                        </Badge>
                        <span v-else class="text-muted-foreground text-sm">Detecting...</span>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Map Configuration -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Map class="h-5 w-5" />
                    Map Configuration
                </CardTitle>
                <CardDescription> Configure the basic settings for your imported map. </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="space-y-2">
                    <Label for="map-name">Map Name</Label>
                    <Input
                        id="map-name"
                        v-model="importConfig.map_name"
                        placeholder="Enter map name"
                        @input="updateImportConfig({ map_name: ($event.target as HTMLInputElement).value })"
                    />
                </div>
            </CardContent>
        </Card>

        <!-- Map Preview -->
        <Card v-if="parsedData">
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Info class="h-5 w-5" />
                    Map Preview
                </CardTitle>
                <CardDescription> Overview of the map that will be imported. </CardDescription>
            </CardHeader>
            <CardContent class="space-y-6">
                <!-- Map Details -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <span class="text-sm font-medium">Size:</span>
                        <p class="text-muted-foreground text-sm">{{ parsedData.map_info.width }} × {{ parsedData.map_info.height }} tiles</p>
                    </div>
                    <div class="space-y-2">
                        <span class="text-sm font-medium">Tile Size:</span>
                        <p class="text-muted-foreground text-sm">
                            {{ parsedData.map_info.tile_width }} × {{ parsedData.map_info.tile_height }} pixels
                        </p>
                    </div>
                </div>

                <Separator />

                <!-- Layers -->
                <div class="space-y-3">
                    <div class="flex items-center gap-2">
                        <Layers class="h-4 w-4" />
                        <span class="font-medium">Layers ({{ parsedData.layers.length }})</span>
                    </div>
                    <div class="space-y-2">
                        <div
                            v-for="(layer, index) in parsedData.layers"
                            :key="index"
                            class="bg-muted flex items-center justify-between rounded-lg p-2"
                        >
                            <div class="flex items-center gap-2">
                                <span class="text-lg">{{ getLayerTypeIcon(layer.type) }}</span>
                                <span class="text-sm font-medium">{{ layer.name }}</span>
                                <Badge :class="getLayerTypeColor(layer.type)" class="text-xs">
                                    {{ layer.type }}
                                </Badge>
                            </div>
                            <span class="text-muted-foreground text-xs"> {{ layer.data?.length || 0 }} items </span>
                        </div>
                    </div>
                </div>

                <Separator />

                <!-- Tilesets -->
                <div class="space-y-3">
                    <div class="flex items-center gap-2">
                        <Palette class="h-4 w-4" />
                        <span class="font-medium">Tilesets ({{ parsedData.tilesets.length }})</span>
                    </div>
                    <div class="space-y-2">
                        <div
                            v-for="(tileset, index) in parsedData.tilesets"
                            :key="index"
                            class="bg-muted flex items-center justify-between rounded-lg p-2"
                        >
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium">{{ tileset.name }}</span>
                                <Badge variant="outline" class="text-xs"> {{ tileset.tile_width }}×{{ tileset.tile_height }} </Badge>
                                <Badge v-if="tileset._missing_image" variant="destructive" class="text-xs"> Missing Image </Badge>
                                <Badge v-if="tileset._requires_upload" variant="secondary" class="text-xs"> Requires Upload </Badge>
                            </div>
                            <span class="text-muted-foreground text-xs"> {{ tileset.tile_count }} tiles </span>
                        </div>
                    </div>

                    <!-- Warning for missing tileset images -->
                    <div v-if="parsedData.tilesets.some((t: any) => t._missing_image)" class="rounded-lg border border-yellow-200 bg-yellow-50 p-4">
                        <div class="flex items-start gap-2">
                            <AlertTriangle class="mt-0.5 h-4 w-4 text-yellow-600" />
                            <div class="text-sm">
                                <p class="font-medium text-yellow-900">Tileset Images Required</p>
                                <p class="mt-1 text-yellow-700">
                                    Some tilesets require their image files to be uploaded before the map can be imported. You'll need to upload the
                                    tileset images in the next step.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Loading State -->
        <Card v-if="isParsing">
            <CardContent class="flex items-center justify-center py-8">
                <div class="flex items-center gap-2">
                    <div class="border-primary h-4 w-4 animate-spin rounded-full border-b-2"></div>
                    <span class="text-muted-foreground text-sm">Parsing map file...</span>
                </div>
            </CardContent>
        </Card>

        <!-- Retry Button -->
        <div v-if="!parsedData && !isParsing" class="text-center">
            <Button @click="parseFile" :disabled="!uploadedFiles?.mainMapFile">
                <FileText class="mr-2 h-4 w-4" />
                Parse File
            </Button>
        </div>
    </div>
</template>
