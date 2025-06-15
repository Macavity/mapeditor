import { SaveStatusType } from '@/types/SaveStatus';
import { computed, readonly, ref } from 'vue';

export function useSaveManager() {
    const hasUnsavedChanges = ref(false);
    const lastSaved = ref<Date | null>(null);
    const isSaving = ref(false);
    const saveError = ref<string | null>(null);
    const autoSaveEnabled = ref(true);
    const autoSaveTimeout = ref<number | null>(null);

    const saveStatus = computed(() => {
        if (isSaving.value) return SaveStatusType.SAVING;
        if (saveError.value) return SaveStatusType.ERROR;
        if (hasUnsavedChanges.value) return SaveStatusType.UNSAVED;
        return SaveStatusType.SAVED;
    });

    function markAsChanged() {
        hasUnsavedChanges.value = true;
        saveError.value = null;
    }

    function markAsSaved() {
        hasUnsavedChanges.value = false;
        lastSaved.value = new Date();
        saveError.value = null;
    }

    function scheduleAutoSave(callback: () => Promise<void>) {
        if (!autoSaveEnabled.value) return;

        if (autoSaveTimeout.value) {
            clearTimeout(autoSaveTimeout.value);
        }

        // Schedule auto-save after 2 seconds of inactivity
        autoSaveTimeout.value = setTimeout(async () => {
            try {
                await callback();
            } catch (error) {
                console.warn('Auto-save failed:', error);
                // Don't show error to user for auto-save failures
            }
        }, 2000);
    }

    async function saveWithErrorHandling(saveFn: () => Promise<void>): Promise<void> {
        if (isSaving.value) return;

        isSaving.value = true;
        saveError.value = null;

        try {
            await saveFn();
            markAsSaved();
        } catch (error) {
            console.error('Save failed:', error);
            saveError.value = error instanceof Error ? error.message : 'Failed to save changes';
            hasUnsavedChanges.value = true;
            throw error; // Re-throw to allow caller to handle
        } finally {
            isSaving.value = false;
        }
    }

    function toggleAutoSave() {
        autoSaveEnabled.value = !autoSaveEnabled.value;
        console.log('Auto-save enabled: ' + autoSaveEnabled.value);

        if (!autoSaveEnabled.value && autoSaveTimeout.value) {
            clearTimeout(autoSaveTimeout.value);
            autoSaveTimeout.value = null;
        }
    }

    function clearSaveTimeout() {
        if (autoSaveTimeout.value) {
            clearTimeout(autoSaveTimeout.value);
            autoSaveTimeout.value = null;
        }
    }

    return {
        // readonly to prevent external mutation
        hasUnsavedChanges: readonly(hasUnsavedChanges),
        lastSaved: readonly(lastSaved),
        isSaving: readonly(isSaving),
        saveError: readonly(saveError),
        autoSaveEnabled: readonly(autoSaveEnabled),
        saveStatus,

        // Methods
        markAsChanged,
        markAsSaved,
        scheduleAutoSave,
        saveWithErrorHandling,
        toggleAutoSave,
        clearSaveTimeout,
    };
}
