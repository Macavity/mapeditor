<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { api } from '@/lib/api';
import { FileText, Info, Layers, Map, Palette } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface Props {
    uploadedFile: { path: string; name: string } | null;
    isParsing: boolean;
    importConfig: {
        map_name: string;
        tileset_mappings: Record<string, string>;
        preserve_uuid: boolean;
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
    if (!props.uploadedFile) return;

    isParsing.value = true;

    try {
        const response = await api.post('/map-import/parse', {
            file_path: props.uploadedFile.path,
        });

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
    () => props.uploadedFile,
    (file) => {
        if (file && !parsedData.value) {
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
                        <span class="text-sm font-medium">File Name:</span>
                        <span class="text-muted-foreground text-sm">{{ uploadedFile?.name }}</span>
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

                <div class="flex items-center space-x-2">
                    <Checkbox
                        id="preserve-uuid"
                        :checked="importConfig.preserve_uuid"
                        @update:checked="updateImportConfig({ preserve_uuid: $event })"
                    />
                    <Label for="preserve-uuid" class="text-sm"> Preserve original UUIDs (may cause conflicts if map already exists) </Label>
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
                            </div>
                            <span class="text-muted-foreground text-xs"> {{ tileset.tile_count }} tiles </span>
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
            <Button @click="parseFile" :disabled="!uploadedFile">
                <FileText class="mr-2 h-4 w-4" />
                Parse File
            </Button>
        </div>
    </div>
</template>
