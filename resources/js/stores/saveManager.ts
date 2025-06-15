import { SaveStatusType } from '@/types/SaveStatus';
import { defineStore } from 'pinia';
import { computed, reactive } from 'vue';

export const useSaveManager = defineStore('saveManager', () => {
    // State
    const state = reactive({
        hasUnsavedChanges: false,
        lastSaved: null as Date | null,
        isSaving: false,
        saveError: null as string | null,
        autoSaveEnabled: true,
        autoSaveTimeout: null as NodeJS.Timeout | null
    });
    
    // Computed
    const saveStatus = computed(() => {
        if (state.isSaving) return SaveStatusType.SAVING;
        if (state.saveError) return SaveStatusType.ERROR;
        if (state.hasUnsavedChanges) return SaveStatusType.UNSAVED;
        return SaveStatusType.SAVED;
    });

    // Actions
    function markAsChanged() {
        state.hasUnsavedChanges = true;
        state.saveError = null;
    }

    function markAsSaved() {
        state.hasUnsavedChanges = false;
        state.lastSaved = new Date();
        state.saveError = null;
    }

    async function saveWithErrorHandling(saveFn: () => Promise<void>): Promise<void> {
        if (state.isSaving) return;

        state.isSaving = true;
        state.saveError = null;

        try {
            await saveFn();
            markAsSaved();
        } catch (error) {
            console.error('Save failed:', error);
            state.saveError = error instanceof Error ? error.message : 'Failed to save changes';
            state.hasUnsavedChanges = true;
            throw error;
        } finally {
            state.isSaving = false;
        }
    }

    function scheduleAutoSave(callback: () => Promise<void>) {
        if (!state.autoSaveEnabled) return;

        if (state.autoSaveTimeout) {
            clearTimeout(state.autoSaveTimeout);
        }

        // Schedule auto-save after 2 seconds of inactivity
        state.autoSaveTimeout = setTimeout(async () => {
            try {
                await callback();
            } catch (error) {
                console.warn('Auto-save failed:', error);
                // Don't show error to user for auto-save failures
            }
        }, 2000);
    }

    function toggleAutoSave() {
        state.autoSaveEnabled = !state.autoSaveEnabled;
        console.log('Auto-save enabled: ' + state.autoSaveEnabled);

        if (!state.autoSaveEnabled && state.autoSaveTimeout) {
            clearTimeout(state.autoSaveTimeout);
            state.autoSaveTimeout = null;
        }
    }

    function clearSaveTimeout() {
        if (state.autoSaveTimeout) {
            clearTimeout(state.autoSaveTimeout);
            state.autoSaveTimeout = null;
        }
    }

    // Cleanup on store reset
    function $reset() {
        clearSaveTimeout();
        state.hasUnsavedChanges = false;
        state.lastSaved = null;
        state.isSaving = false;
        state.saveError = null;
        state.autoSaveEnabled = true;
    }

    return {
        // State
        hasUnsavedChanges: computed({
            get: () => state.hasUnsavedChanges,
            set: (value) => { state.hasUnsavedChanges = value; }
        }),
        lastSaved: computed({
            get: () => state.lastSaved,
            set: (value) => { state.lastSaved = value; }
        }),
        isSaving: computed({
            get: () => state.isSaving,
            set: (value) => { state.isSaving = value; }
        }),
        saveError: computed({
            get: () => state.saveError,
            set: (value) => { state.saveError = value; }
        }),
        autoSaveEnabled: computed({
            get: () => state.autoSaveEnabled,
            set: (value) => { state.autoSaveEnabled = value; }
        }),
        saveStatus,

        // Actions
        markAsChanged,
        markAsSaved,
        saveWithErrorHandling,
        scheduleAutoSave,
        toggleAutoSave,
        clearSaveTimeout,
        $reset,
    };
});
