<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { api } from '@/lib/api';
import { AlertCircle, FileText, Upload, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
    isUploading: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'files-uploaded': [
        fileData: {
            files: Array<{ path: string; name: string; extension: string }>;
            mainMapFile: string | null;
            fieldTypeFile: string | null;
        },
    ];
    error: [message: string];
}>();

const fileInput = ref<HTMLInputElement>();
const selectedFiles = ref<File[]>([]);
const dragOver = ref(false);

const supportedFormats = ['json', 'tmx', 'js'];
const maxFileSize = 10 * 1024 * 1024; // 10MB

const hasLegacyFiles = computed(() => {
    return selectedFiles.value.some((file) => {
        const extension = file.name.split('.').pop()?.toLowerCase();
        return extension === 'js';
    });
});

const handleFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files) {
        const newFiles = Array.from(target.files);
        selectedFiles.value.push(...newFiles);
    }
    // Reset the input so the same file can be selected again
    if (target) target.value = '';
};

const handleDragOver = (event: DragEvent) => {
    event.preventDefault();
    dragOver.value = true;
};

const handleDragLeave = (event: DragEvent) => {
    event.preventDefault();
    dragOver.value = false;
};

const handleDrop = (event: DragEvent) => {
    event.preventDefault();
    dragOver.value = false;

    if (event.dataTransfer?.files) {
        const newFiles = Array.from(event.dataTransfer.files);
        selectedFiles.value.push(...newFiles);
    }
};

const removeFile = (index: number) => {
    selectedFiles.value.splice(index, 1);
};

const validateFiles = (files: File[]): string | null => {
    for (const file of files) {
        const extension = file.name.split('.').pop()?.toLowerCase();

        if (!extension || !supportedFormats.includes(extension)) {
            return `Unsupported file format: ${file.name}. Supported formats: ${supportedFormats.join(', ')}`;
        }

        if (file.size > maxFileSize) {
            return `File size too large: ${file.name}. Maximum size: ${Math.round(maxFileSize / 1024 / 1024)}MB`;
        }
    }

    return null;
};

const uploadFiles = async () => {
    if (selectedFiles.value.length === 0) return;

    const validationError = validateFiles(selectedFiles.value);
    if (validationError) {
        emit('error', validationError);
        return;
    }

    try {
        const formData = new FormData();
        selectedFiles.value.forEach((file) => {
            formData.append('files[]', file);
        });

        const response = await api.post('/map-import/upload', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        emit('files-uploaded', {
            files: response.data.files,
            mainMapFile: response.data.main_map_file,
            fieldTypeFile: response.data.field_type_file,
        });
    } catch (error: any) {
        const message = error.response?.data?.message || 'Failed to upload files';
        emit('error', message);
    }
};

const openFileDialog = () => {
    fileInput.value?.click();
};
</script>

<template>
    <div class="space-y-6">
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Upload class="h-5 w-5" />
                    Upload Map Files
                </CardTitle>
                <CardDescription>
                    Select map files to import. For legacy JS maps, you can upload both the main map file and the field type file (ending with
                    _ft.js).
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <!-- File Input (Hidden) -->
                <input ref="fileInput" type="file" accept=".json,.tmx,.js" multiple class="hidden" @change="handleFileSelect" />

                <!-- Drag & Drop Area -->
                <div
                    :class="[
                        'rounded-lg border-2 border-dashed p-8 text-center transition-colors',
                        dragOver ? 'border-primary bg-primary/5' : 'border-muted-foreground/25 hover:border-muted-foreground/50',
                    ]"
                    @dragover="handleDragOver"
                    @dragleave="handleDragLeave"
                    @drop="handleDrop"
                >
                    <Upload class="text-muted-foreground mx-auto mb-4 h-12 w-12" />
                    <div class="space-y-2">
                        <p class="text-lg font-medium">
                            Drop your map files here, or
                            <button type="button" class="text-primary hover:underline" @click="openFileDialog">browse</button>
                        </p>
                        <p class="text-muted-foreground text-sm">
                            Supported formats: {{ supportedFormats.join(', ').toUpperCase() }}
                            <br />
                            Maximum file size: {{ Math.round(maxFileSize / 1024 / 1024) }}MB per file
                        </p>
                    </div>
                </div>

                <!-- Selected Files -->
                <div v-if="selectedFiles.length > 0" class="space-y-2">
                    <Label>Selected Files:</Label>
                    <div class="space-y-2">
                        <div v-for="(file, index) in selectedFiles" :key="index" class="bg-muted flex items-center gap-2 rounded-lg p-3">
                            <FileText class="text-muted-foreground h-4 w-4" />
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <p class="truncate text-sm font-medium">{{ file.name }}</p>
                                    <Badge v-if="file.name.endsWith('_ft.js')" variant="secondary" class="text-xs">Field Type</Badge>
                                    <Badge v-else-if="file.name.split('.').pop()?.toLowerCase() === 'js'" variant="outline" class="text-xs"
                                        >Map</Badge
                                    >
                                </div>
                                <p class="text-muted-foreground text-xs">{{ (file.size / 1024 / 1024).toFixed(2) }}MB</p>
                            </div>
                            <Button variant="outline" size="sm" @click="removeFile(index)">
                                <X class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Legacy JS Files Info -->
                <div v-if="hasLegacyFiles" class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                    <div class="flex items-start gap-2">
                        <AlertCircle class="mt-0.5 h-4 w-4 text-blue-600" />
                        <div class="text-sm">
                            <p class="font-medium text-blue-900">Legacy JS Map Detected</p>
                            <p class="mt-1 text-blue-700">
                                For legacy JavaScript maps, you can optionally upload a field type file (ending with _ft.js) to import field type
                                data. If you don't have the field type file, the map will be imported without it.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Upload Button -->
                <Button :disabled="selectedFiles.length === 0 || props.isUploading" :loading="props.isUploading" class="w-full" @click="uploadFiles">
                    <Upload v-if="!props.isUploading" class="mr-2 h-4 w-4" />
                    {{ props.isUploading ? 'Uploading...' : `Upload ${selectedFiles.length} File${selectedFiles.length !== 1 ? 's' : ''}` }}
                </Button>
            </CardContent>
        </Card>

        <!-- Help Information -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <AlertCircle class="h-5 w-5" />
                    Import Information
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-3 text-sm">
                <div><strong>JSON Format:</strong> Maps exported from this editor</div>
                <div><strong>TMX Format:</strong> Tiled Map Editor files</div>
                <div><strong>JS Format:</strong> Legacy JavaScript map files</div>
                <div class="text-muted-foreground">
                    <strong>Legacy JS Maps:</strong> You can upload both the main map file and a field type file (ending with _ft.js) to import field
                    type data. The field type file is optional.
                </div>
                <div class="text-muted-foreground">After uploading, you'll be able to review the map details and configure tileset mappings.</div>
            </CardContent>
        </Card>
    </div>
</template>
