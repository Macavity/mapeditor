import { computed, readonly, ref } from 'vue';

export function useSaveManager() {
    const hasUnsavedChanges = ref(false);
    const lastSaved = ref<Date | null>(null);
    const isSaving = ref(false);
    const saveError = ref<string | null>(null);
    const autoSaveEnabled = ref(true);
    const autoSaveTimeout = ref<number | null>(null);

    const saveStatus = computed(() => {
        if (isSaving.value) return 'saving';
        if (saveError.value) return 'error';
        if (hasUnsavedChanges.value) return 'unsaved';
        return 'saved';
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

    async function saveWithErrorHandling(saveCallback: () => Promise<void>) {
        if (isSaving.value) return false;

        isSaving.value = true;
        saveError.value = null;

        try {
            await saveCallback();
            markAsSaved();
            return true;
        } catch (error) {
            saveError.value = error instanceof Error ? error.message : 'Save failed';
            return false;
        } finally {
            isSaving.value = false;
        }
    }

    function toggleAutoSave() {
        autoSaveEnabled.value = !autoSaveEnabled.value;

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
