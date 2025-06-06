<script setup lang="ts">
import { useEditorStore } from '@/stores/editorStore';
import { AlertCircle, Check, Clock, Save } from 'lucide-vue-next';
import { computed } from 'vue';

const store = useEditorStore();

const saveStatus = computed(() => {
    if (store.isSaving) return 'saving';
    if (store.saveError) return 'error';
    if (store.hasUnsavedChanges) return 'unsaved';
    return 'saved';
});

const saveStatusText = computed(() => {
    switch (saveStatus.value) {
        case 'saving':
            return 'Saving...';
        case 'error':
            return 'Save failed';
        case 'unsaved':
            return 'Unsaved changes';
        case 'saved':
            return store.lastSaved ? `Saved ${formatTime(store.lastSaved)}` : 'Saved';
    }
});

const saveStatusIcon = computed(() => {
    switch (saveStatus.value) {
        case 'saving':
            return Clock;
        case 'error':
            return AlertCircle;
        case 'unsaved':
            return Save;
        case 'saved':
            return Check;
    }
});

const saveStatusColor = computed(() => {
    switch (saveStatus.value) {
        case 'saving':
            return 'text-blue-600';
        case 'error':
            return 'text-red-600';
        case 'unsaved':
            return 'text-yellow-600';
        case 'saved':
            return 'text-green-600';
    }
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
    const success = await store.manualSave();
    // Could show a toast notification here
}
</script>

<template>
    <div class="flex items-center gap-2 text-sm">
        <!-- Save Status -->
        <div class="flex items-center gap-1.5" :class="saveStatusColor">
            <component :is="saveStatusIcon" class="h-4 w-4" />
            <span>{{ saveStatusText }}</span>
        </div>

        <!-- Error Details -->
        <div v-if="store.saveError" class="text-xs text-red-600">
            {{ store.saveError }}
        </div>

        <!-- Manual Save Button -->
        <button
            v-if="store.hasUnsavedChanges && !store.isSaving"
            @click="handleManualSave"
            class="rounded bg-blue-600 px-2 py-1 text-xs text-white transition-colors hover:bg-blue-700"
            :disabled="store.isSaving"
        >
            Save Now
        </button>

        <!-- Auto-save Toggle -->
        <button
            @click="store.toggleAutoSave()"
            class="rounded border px-2 py-1 text-xs transition-colors"
            :class="
                store.autoSaveEnabled
                    ? 'border-green-300 bg-green-50 text-green-700 hover:bg-green-100'
                    : 'border-gray-300 bg-gray-50 text-gray-700 hover:bg-gray-100'
            "
        >
            Auto-save: {{ store.autoSaveEnabled ? 'ON' : 'OFF' }}
        </button>
    </div>
</template>
