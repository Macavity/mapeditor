<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { api } from '@/lib/api';
import { AlertTriangle, CheckCircle, ChevronDown, Link, Palette, Plus, Upload, X } from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
    parsedData: {
        tilesets: ImportedTileset[];
        suggested_tilesets: Record<string, any[]>;
    } | null;
    importConfig: {
        tileset_mappings: Record<string, string>;
        tileset_images: Record<string, File>;
    };
    isImporting: boolean;
}

interface ImportedTileset {
    original_name: string;
    formatted_name: string;
    tile_count: number;
    max_tile_id: number;
    image_exists: boolean;
    existing_tileset: {
        uuid: string;
        name: string;
        image_path: string;
    } | null;
    requires_upload: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'import-complete': [result: any];
    error: [message: string];
    'update:import-config': [config: any];
}>();

const existingTilesets = ref<any[]>([]);
const isLoadingTilesets = ref(false);
const fileInputs = ref<Record<string, HTMLInputElement>>({});

const updateTilesetMapping = (importedUuid: string, targetUuid: string) => {
    const newMappings = { ...props.importConfig.tileset_mappings };
    newMappings[importedUuid] = targetUuid;

    // If switching away from 'create_new', remove any uploaded image
    if (targetUuid !== 'create_new') {
        const newImages = { ...props.importConfig.tileset_images };
        delete newImages[importedUuid];
        emit('update:import-config', {
            ...props.importConfig,
            tileset_mappings: newMappings,
            tileset_images: newImages,
        });
    } else {
        emit('update:import-config', { ...props.importConfig, tileset_mappings: newMappings });
    }
};

const handleTilesetImageUpload = (tilesetKey: string, event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (file) {
        // Validate file type
        const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            emit('error', 'Invalid file type. Please upload a PNG, JPEG, GIF, or WebP image.');
            return;
        }

        // Validate file size (max 10MB)
        if (file.size > 10 * 1024 * 1024) {
            emit('error', 'File too large. Please upload an image smaller than 10MB.');
            return;
        }

        const newImages = { ...props.importConfig.tileset_images };
        newImages[tilesetKey] = file;
        emit('update:import-config', { ...props.importConfig, tileset_images: newImages });
    }
};

const removeTilesetImage = (tilesetKey: string) => {
    const newImages = { ...props.importConfig.tileset_images };
    delete newImages[tilesetKey];
    emit('update:import-config', { ...props.importConfig, tileset_images: newImages });
};

const triggerFileInput = (tilesetKey: string) => {
    fileInputs.value[tilesetKey]?.click();
};

const getImageUrl = (file: File) => {
    return URL.createObjectURL(file);
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
    const tilesetKey = importedTileset.original_name;
    const mapping = props.importConfig.tileset_mappings[tilesetKey];

    if (mapping === 'create_new') {
        const hasImage = props.importConfig.tileset_images[tilesetKey];
        if (importedTileset.requires_upload && !hasImage) {
            return {
                status: 'needs_image',
                label: 'Needs Image',
                icon: AlertTriangle,
                color: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
            };
        }
        return {
            status: 'new',
            label: 'Create New',
            icon: Plus,
            color: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        };
    } else if (mapping) {
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

const getSelectedLabel = (importedTileset: ImportedTileset) => {
    const tilesetKey = importedTileset.original_name;
    const mapping = props.importConfig.tileset_mappings[tilesetKey];

    if (mapping === 'create_new') {
        return 'Create New Tileset';
    } else if (mapping) {
        // Find the existing tileset by UUID
        const existingTileset = existingTilesets.value.find((ts) => ts.uuid === mapping);
        return existingTileset?.name || 'Unknown';
    }
    return 'Select tileset mapping';
};

const canProceedWithImport = () => {
    if (!props.parsedData?.tilesets) return false;

    return props.parsedData.tilesets.every((tileset) => {
        const mapping = props.importConfig.tileset_mappings[tileset.original_name];

        // If creating new and requires upload, must have image
        if (mapping === 'create_new' && tileset.requires_upload) {
            return props.importConfig.tileset_images[tileset.original_name];
        }

        return true;
    });
};
</script>

<template>
    <div class="space-y-6">
        <!-- Warning for missing tileset images -->
        <div v-if="parsedData?.tilesets?.some((t: any) => t.requires_upload)" class="rounded-lg border border-yellow-200 bg-yellow-50 p-4">
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
            <Card v-for="importedTileset in parsedData.tilesets" :key="importedTileset.original_name">
                <CardHeader class="pb-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <Palette class="h-4 w-4" />
                            <span class="font-medium">{{ importedTileset.formatted_name }}</span>
                            <Badge v-if="importedTileset.requires_upload" variant="destructive" class="text-xs"> Missing Image </Badge>
                        </div>
                        <Badge :class="getTilesetMappingStatus(importedTileset).color" class="text-xs">
                            <component :is="getTilesetMappingStatus(importedTileset).icon" class="mr-1 h-3 w-3" />
                            {{ getTilesetMappingStatus(importedTileset).label }}
                            <span
                                v-if="
                                    importedTileset.existing_tileset &&
                                    props.importConfig.tileset_mappings[importedTileset.original_name] === importedTileset.existing_tileset.uuid
                                "
                                class="ml-1"
                                >(Auto)</span
                            >
                        </Badge>
                    </div>
                </CardHeader>

                <CardContent class="space-y-4">
                    <!-- Mapping Selection -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium">Map to:</label>
                        <DropdownMenu>
                            <DropdownMenuTrigger
                                class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus:ring-ring flex h-10 items-center justify-between rounded-md border px-3 py-2 text-sm focus:ring-2 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <span class="block truncate">{{ getSelectedLabel(importedTileset) }}</span>
                                <ChevronDown class="h-4 w-4 opacity-50" />
                            </DropdownMenuTrigger>
                            <DropdownMenuContent class="w-full">
                                <DropdownMenuItem @click="updateTilesetMapping(importedTileset.original_name, 'create_new')">
                                    <div class="flex items-center gap-2">
                                        <Plus class="h-4 w-4" />
                                        Create New Tileset
                                    </div>
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    v-for="existingTileset in existingTilesets"
                                    :key="existingTileset.uuid"
                                    @click="updateTilesetMapping(importedTileset.original_name, existingTileset.uuid)"
                                >
                                    <div class="flex items-center gap-2">
                                        <Link class="h-4 w-4" />
                                        {{ existingTileset.name }}
                                    </div>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>

                    <!-- Image Upload for Create New Tilesets -->
                    <div
                        v-if="props.importConfig.tileset_mappings[importedTileset.original_name] === 'create_new' && importedTileset.requires_upload"
                        class="space-y-2"
                    >
                        <label class="text-sm font-medium">Tileset Image:</label>

                        <!-- Image Upload Interface -->
                        <div v-if="!props.importConfig.tileset_images[importedTileset.original_name]" class="space-y-2">
                            <div class="rounded-lg border-2 border-dashed border-gray-300 p-4 text-center">
                                <Upload class="mx-auto mb-2 h-8 w-8 text-gray-400" />
                                <p class="mb-2 text-sm text-gray-600">Upload tileset image</p>
                                <p class="mb-3 text-xs text-gray-500">PNG, JPEG, GIF, or WebP (max 10MB)</p>
                                <Button variant="outline" size="sm" @click="triggerFileInput(importedTileset.original_name)"> Choose File </Button>
                                <input
                                    :ref="
                                        (el) => {
                                            if (el) fileInputs[importedTileset.original_name] = el as HTMLInputElement;
                                        }
                                    "
                                    type="file"
                                    accept="image/*"
                                    class="hidden"
                                    @change="handleTilesetImageUpload(importedTileset.original_name, $event)"
                                />
                            </div>
                        </div>

                        <!-- Image Preview -->
                        <div v-else class="space-y-2">
                            <div class="flex items-center gap-2 rounded-lg bg-gray-50 p-3">
                                <img
                                    :src="getImageUrl(props.importConfig.tileset_images[importedTileset.original_name])"
                                    :alt="importedTileset.formatted_name"
                                    class="h-12 w-12 rounded border object-cover"
                                />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium">
                                        {{ props.importConfig.tileset_images[importedTileset.original_name].name }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ (props.importConfig.tileset_images[importedTileset.original_name].size / 1024 / 1024).toFixed(1) }} MB
                                    </p>
                                </div>
                                <Button variant="ghost" size="sm" @click="removeTilesetImage(importedTileset.original_name)">
                                    <X class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- Suggested Tilesets -->
                    <div v-if="parsedData.suggested_tilesets[importedTileset.original_name]?.length" class="space-y-2">
                        <label class="text-muted-foreground text-sm font-medium">Suggested matches:</label>
                        <div class="space-y-1">
                            <div
                                v-for="suggestion in parsedData.suggested_tilesets[importedTileset.original_name]"
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
                            <span class="font-medium">Max Tile ID:</span>
                            <span class="text-muted-foreground ml-1">{{ importedTileset.max_tile_id }}</span>
                        </div>
                    </div>
                </CardContent>
            </Card>
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
