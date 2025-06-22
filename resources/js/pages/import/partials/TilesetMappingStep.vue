<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { api } from '@/lib/api';
import { AlertTriangle, CheckCircle, Link, Palette, Plus } from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
    parsedData: {
        tilesets: any[];
        suggested_tilesets: Record<string, any[]>;
    } | null;
    importConfig: {
        tileset_mappings: Record<string, string>;
    };
    isImporting: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'import-complete': [result: any];
    error: [message: string];
    'update:import-config': [config: any];
}>();

const existingTilesets = ref<any[]>([]);
const isLoadingTilesets = ref(false);

const updateTilesetMapping = (importedUuid: string, targetUuid: string) => {
    const newMappings = { ...props.importConfig.tileset_mappings };
    newMappings[importedUuid] = targetUuid;
    emit('update:import-config', { ...props.importConfig, tileset_mappings: newMappings });
};

const loadExistingTilesets = async () => {
    isLoadingTilesets.value = true;
    try {
        const response = await api.get('/tile-sets');
        existingTilesets.value = response.data.data;
    } catch (error: any) {
        emit('error', 'Failed to load existing tilesets');
    } finally {
        isLoadingTilesets.value = false;
    }
};

// Load tilesets on mount
loadExistingTilesets();

const getTilesetMappingStatus = (importedTileset: any) => {
    const mapping = props.importConfig.tileset_mappings[importedTileset.uuid];

    if (mapping === 'create_new') {
        return {
            status: 'new',
            label: 'Create New',
            icon: Plus,
            color: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        };
    } else if (mapping && mapping !== 'create_new') {
        const existingTileset = existingTilesets.value.find((ts) => ts.uuid === mapping);
        return {
            status: 'mapped',
            label: existingTileset?.name || 'Unknown',
            icon: Link,
            color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        };
    } else {
        return {
            status: 'unmapped',
            label: 'Not Mapped',
            icon: AlertTriangle,
            color: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        };
    }
};

const getSimilarityColor = (similarity: number) => {
    if (similarity >= 0.8) return 'text-green-600';
    if (similarity >= 0.6) return 'text-yellow-600';
    if (similarity >= 0.4) return 'text-orange-600';
    return 'text-red-600';
};
</script>

<template>
    <div class="space-y-6">
        <!-- Warning for missing tileset images -->
        <div v-if="parsedData?.tilesets?.some((t: any) => t._missing_image)" class="rounded-lg border border-yellow-200 bg-yellow-50 p-4">
            <div class="flex items-start gap-2">
                <AlertTriangle class="mt-0.5 h-4 w-4 text-yellow-600" />
                <div class="text-sm">
                    <p class="font-medium text-yellow-900">Tileset Images Required</p>
                    <p class="mt-1 text-yellow-700">
                        Some tilesets require their image files to be uploaded before the map can be imported. You'll need to upload the tileset
                        images before proceeding with the import.
                    </p>
                </div>
            </div>
        </div>

        <!-- Instructions -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Palette class="h-5 w-5" />
                    Tileset Mapping
                </CardTitle>
                <CardDescription>
                    Map imported tilesets to existing ones or create new ones. The system will suggest similar tilesets based on name matching.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div class="space-y-2 text-sm">
                    <div class="flex items-center gap-2">
                        <CheckCircle class="h-4 w-4 text-green-600" />
                        <span>Green: High similarity match</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <AlertTriangle class="h-4 w-4 text-yellow-600" />
                        <span>Yellow: Medium similarity match</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <AlertTriangle class="h-4 w-4 text-red-600" />
                        <span>Red: Low similarity or no match</span>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Tileset Mappings -->
        <div v-if="parsedData?.tilesets" class="space-y-4">
            <div v-for="importedTileset in parsedData.tilesets" :key="importedTileset.uuid" class="card border">
                <CardHeader class="pb-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <Palette class="h-4 w-4" />
                            <span class="font-medium">{{ importedTileset.name }}</span>
                            <Badge variant="outline" class="text-xs"> {{ importedTileset.tile_width }}×{{ importedTileset.tile_height }} </Badge>
                            <Badge v-if="importedTileset._missing_image" variant="destructive" class="text-xs"> Missing Image </Badge>
                            <Badge v-if="importedTileset._requires_upload" variant="secondary" class="text-xs"> Requires Upload </Badge>
                        </div>
                        <Badge :class="getTilesetMappingStatus(importedTileset).color" class="text-xs">
                            <component :is="getTilesetMappingStatus(importedTileset).icon" class="mr-1 h-3 w-3" />
                            {{ getTilesetMappingStatus(importedTileset).label }}
                        </Badge>
                    </div>
                </CardHeader>

                <CardContent class="space-y-4">
                    <!-- Mapping Selection -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium">Map to:</label>
                        <Select
                            :value="importConfig.tileset_mappings[importedTileset.uuid] || 'create_new'"
                            @update:value="updateTilesetMapping(importedTileset.uuid, $event)"
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Select tileset mapping" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="create_new">
                                    <div class="flex items-center gap-2">
                                        <Plus class="h-4 w-4" />
                                        Create New Tileset
                                    </div>
                                </SelectItem>
                                <SelectItem v-for="existingTileset in existingTilesets" :key="existingTileset.uuid" :value="existingTileset.uuid">
                                    <div class="flex items-center gap-2">
                                        <Link class="h-4 w-4" />
                                        {{ existingTileset.name }}
                                    </div>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Suggested Tilesets -->
                    <div v-if="parsedData.suggested_tilesets[importedTileset.uuid]?.length" class="space-y-2">
                        <label class="text-muted-foreground text-sm font-medium">Suggested matches:</label>
                        <div class="space-y-1">
                            <div
                                v-for="suggestion in parsedData.suggested_tilesets[importedTileset.uuid]"
                                :key="suggestion.uuid"
                                class="bg-muted flex items-center justify-between rounded p-2 text-sm"
                            >
                                <span>{{ suggestion.name }}</span>
                                <span :class="getSimilarityColor(suggestion.similarity)"> {{ Math.round(suggestion.similarity * 100) }}% match </span>
                            </div>
                        </div>
                    </div>

                    <!-- Tileset Details -->
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="font-medium">Tile Count:</span>
                            <span class="text-muted-foreground ml-1">{{ importedTileset.tile_count }}</span>
                        </div>
                        <div>
                            <span class="font-medium">Image Size:</span>
                            <span class="text-muted-foreground ml-1"> {{ importedTileset.image_width }}×{{ importedTileset.image_height }} </span>
                        </div>
                    </div>
                </CardContent>
            </div>
        </div>

        <!-- Loading State -->
        <Card v-if="isLoadingTilesets">
            <CardContent class="flex items-center justify-center py-8">
                <div class="flex items-center gap-2">
                    <div class="border-primary h-4 w-4 animate-spin rounded-full border-b-2"></div>
                    <span class="text-muted-foreground text-sm">Loading existing tilesets...</span>
                </div>
            </CardContent>
        </Card>

        <!-- Summary -->
        <Card>
            <CardHeader>
                <CardTitle>Mapping Summary</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span>Total Tilesets:</span>
                        <span class="font-medium">{{ parsedData?.tilesets?.length || 0 }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Will Create New:</span>
                        <span class="font-medium text-blue-600">
                            {{ Object.values(importConfig.tileset_mappings).filter((m) => m === 'create_new').length }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span>Will Use Existing:</span>
                        <span class="font-medium text-green-600">
                            {{ Object.values(importConfig.tileset_mappings).filter((m) => m !== 'create_new').length }}
                        </span>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
