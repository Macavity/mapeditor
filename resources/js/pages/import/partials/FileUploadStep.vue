<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { api } from '@/lib/api';
import { AlertCircle, FileText, Upload } from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
    isUploading: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'file-uploaded': [fileData: { path: string; name: string }];
    error: [message: string];
}>();

const fileInput = ref<HTMLInputElement>();
const selectedFile = ref<File | null>(null);
const dragOver = ref(false);

const supportedFormats = ['json', 'tmx', 'js'];
const maxFileSize = 10 * 1024 * 1024; // 10MB

const handleFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        selectedFile.value = target.files[0];
    }
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

    if (event.dataTransfer?.files && event.dataTransfer.files[0]) {
        selectedFile.value = event.dataTransfer.files[0];
    }
};

const validateFile = (file: File): string | null => {
    const extension = file.name.split('.').pop()?.toLowerCase();

    if (!extension || !supportedFormats.includes(extension)) {
        return `Unsupported file format. Supported formats: ${supportedFormats.join(', ')}`;
    }

    if (file.size > maxFileSize) {
        return `File size too large. Maximum size: ${Math.round(maxFileSize / 1024 / 1024)}MB`;
    }

    return null;
};

const uploadFile = async () => {
    if (!selectedFile.value) return;

    const validationError = validateFile(selectedFile.value);
    if (validationError) {
        emit('error', validationError);
        return;
    }

    try {
        const formData = new FormData();
        formData.append('file', selectedFile.value);

        const response = await api.post('/map-import/upload', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        emit('file-uploaded', {
            path: response.data.file_path,
            name: response.data.file_name,
        });
    } catch (error: any) {
        const message = error.response?.data?.message || 'Failed to upload file';
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
                    Upload Map File
                </CardTitle>
                <CardDescription> Select a map file to import. Supported formats: JSON, TMX, and JS files. </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <!-- File Input (Hidden) -->
                <input ref="fileInput" type="file" accept=".json,.tmx,.js" class="hidden" @change="handleFileSelect" />

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
                            Drop your map file here, or
                            <button type="button" class="text-primary hover:underline" @click="openFileDialog">browse</button>
                        </p>
                        <p class="text-muted-foreground text-sm">
                            Supported formats: {{ supportedFormats.join(', ').toUpperCase() }}
                            <br />
                            Maximum file size: {{ Math.round(maxFileSize / 1024 / 1024) }}MB
                        </p>
                    </div>
                </div>

                <!-- Selected File -->
                <div v-if="selectedFile" class="space-y-2">
                    <Label>Selected File:</Label>
                    <div class="bg-muted flex items-center gap-2 rounded-lg p-3">
                        <FileText class="text-muted-foreground h-4 w-4" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ selectedFile.name }}</p>
                            <p class="text-muted-foreground text-xs">{{ (selectedFile.size / 1024 / 1024).toFixed(2) }}MB</p>
                        </div>
                        <Button variant="outline" size="sm" @click="selectedFile = null"> Remove </Button>
                    </div>
                </div>

                <!-- Upload Button -->
                <Button :disabled="!selectedFile || props.isUploading" :loading="props.isUploading" class="w-full" @click="uploadFile">
                    <Upload v-if="!props.isUploading" class="mr-2 h-4 w-4" />
                    {{ props.isUploading ? 'Uploading...' : 'Upload File' }}
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
                <div class="text-muted-foreground">After uploading, you'll be able to review the map details and configure tileset mappings.</div>
            </CardContent>
        </Card>
    </div>
</template>
