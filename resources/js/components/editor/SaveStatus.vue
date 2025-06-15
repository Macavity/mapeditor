<script setup lang="ts">
import { useEditorStore } from '@/stores/editorStore';
import { useSaveManager } from '@/stores/saveManager';
import { SaveStatusType } from '@/types/SaveStatus';
import { TestId } from '@/types/TestId';
import { AlertCircle, Check, Clock, Save } from 'lucide-vue-next';
import { computed, watch } from 'vue';

interface SaveStatusConfig {
    text: () => string;
    icon: any;
    color: string;
}

const editorStore = useEditorStore();
const saveManager = useSaveManager();

// Auto-save handler
const handleAutoSave = async () => {
    if (saveManager.hasUnsavedChanges && saveManager.autoSaveEnabled) {
        await saveManager.saveWithErrorHandling(async () => {
            await editorStore.saveAllLayers();
        });
    }
};

// Watch for changes and trigger auto-save
watch(
    () => saveManager.saveStatus,
    async (newStatus: SaveStatusType) => {
        if (newStatus === SaveStatusType.UNSAVED && saveManager.autoSaveEnabled) {
            await handleAutoSave();
        }
    },
    { immediate: true },
);

const saveStatusConfig: Record<SaveStatusType, SaveStatusConfig> = {
    [SaveStatusType.SAVING]: {
        text: () => 'Saving...',
        icon: Clock,
        color: 'text-blue-600',
    },
    [SaveStatusType.ERROR]: {
        text: () => 'Save failed',
        icon: AlertCircle,
        color: 'text-red-600',
    },
    [SaveStatusType.UNSAVED]: {
        text: () => 'Unsaved changes',
        icon: Save,
        color: 'text-yellow-600',
    },
    [SaveStatusType.SAVED]: {
        text: () => (saveManager.lastSaved ? `Saved ${formatTime(saveManager.lastSaved)}` : 'Saved'),
        icon: Check,
        color: 'text-green-600',
    },
};

const currentStatusConfig = computed(() => {
    return saveStatusConfig[saveManager.saveStatus] || saveStatusConfig[SaveStatusType.SAVED];
});

function formatTime(date: Date | string): string {
    const now = new Date();
    const dateObj = typeof date === 'string' ? new Date(date) : date;
    const diffMs = now.getTime() - dateObj.getTime();
    const diffSec = Math.floor(diffMs / 1000);
    const diffMin = Math.floor(diffSec / 60);

    if (diffSec < 60) return 'just now';
    if (diffMin < 60) return `${diffMin}m ago`;
    return dateObj.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

async function handleManualSave() {
    // Use saveManager to handle the save with error handling
    await saveManager.saveWithErrorHandling(async () => {
        editorStore.saveAllLayers();
    });
}
</script>

<template>
    <div :data-testid="TestId.SAVE_STATUS" class="flex items-center gap-2 text-sm">
        <!-- Save Status -->
        <div class="flex items-center gap-1.5" :class="currentStatusConfig.color">
            <component 
                :is="currentStatusConfig.icon" 
                :data-testid="TestId.SAVE_STATUS_ICON" 
                class="h-4 w-4" 
            />
            <span :data-testid="TestId.SAVE_STATUS_TEXT">{{ currentStatusConfig.text() }}</span>
        </div>

        <!-- Error Details -->
        <div 
            v-if="saveManager.saveError" 
            :data-testid="TestId.SAVE_STATUS_ERROR"
            class="text-xs text-red-600"
        >
            {{ saveManager.saveError }}
        </div>

        <!-- Manual Save Button -->
        <button
            v-if="saveManager.hasUnsavedChanges && !saveManager.isSaving"
            :data-testid="TestId.SAVE_BUTTON"
            @click="handleManualSave"
            class="rounded bg-blue-600 px-2 py-1 text-xs text-white transition-colors hover:bg-blue-700"
            :disabled="saveManager.isSaving"
        >
            Save Now
        </button>

        <!-- Auto-save Toggle -->
        <button
            :data-testid="TestId.AUTO_SAVE_TOGGLE"
            @click="saveManager.toggleAutoSave()"
            class="rounded border px-2 py-1 text-xs transition-colors"
            :class="
                saveManager.autoSaveEnabled
                    ? 'border-green-300 bg-green-50 text-green-700 hover:bg-green-100'
                    : 'border-gray-300 bg-gray-50 text-gray-700 hover:bg-gray-100'
            "
        >
            Auto-save: {{ saveManager.autoSaveEnabled ? 'ON' : 'OFF' }}
        </button>
    </div>
</template>
