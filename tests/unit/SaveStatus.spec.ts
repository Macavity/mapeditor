import SaveStatus from '@/components/editor/SaveStatus.vue';
import { useEditorStore } from '@/stores/editorStore';
import { useSaveManager } from '@/stores/saveManager';
import { TestId } from '@/types/TestId';
import { createTestingPinia } from '@pinia/testing';
import { mount } from '@vue/test-utils';
import { setActivePinia } from 'pinia';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';

// Mock only the lucide-vue-next icons
vi.mock('lucide-vue-next', () => ({
    AlertCircle: { template: '<div class="mock-icon">AlertCircle</div>' },
    Check: { template: '<div class="mock-icon">Check</div>' },
    Clock: { template: '<div class="mock-icon">Clock</div>' },
    Save: { template: '<div class="mock-icon">Save</div>' },
}));

describe('SaveStatus.vue', () => {
    let wrapper: any;
    let editorStore: ReturnType<typeof useEditorStore>;
    let saveManager: ReturnType<typeof useSaveManager>;
    let clock: any;

    const createWrapper = async () => {
        const pinia = createTestingPinia({
            createSpy: vi.fn,
            stubActions: false,
        });
        setActivePinia(pinia);

        // Create stores
        saveManager = useSaveManager();
        editorStore = useEditorStore(pinia);

        // Mock editor store method
        editorStore.saveAllLayers = vi.fn().mockResolvedValue({ success: true });

        // Mount the component
        wrapper = mount(SaveStatus, {
            global: {
                plugins: [pinia],
            },
        });

        // Wait for initial render
        await nextTick();

        return { wrapper, saveManager, editorStore };
    };

    beforeEach(() => {
        // Setup fake timers
        clock = vi.useFakeTimers();
    });

    afterEach(() => {
        // Cleanup
        vi.clearAllMocks();
        vi.useRealTimers();
    });

    it('renders saved state by default', async () => {
        await createWrapper();

        const statusText = wrapper.find(`[data-testid="${TestId.SAVE_STATUS_TEXT}"]`);
        const statusIcon = wrapper.find(`[data-testid="${TestId.SAVE_STATUS_ICON}"]`);

        expect(statusText.text()).toContain('Saved');
        expect(statusIcon.text()).toBe('Check');
        expect(statusText.classes()).toContain('text-green-600');
    });

    it('shows saving state when isSaving is true', async () => {
        const { wrapper, saveManager } = await createWrapper();

        // Trigger saving state by calling an action that sets isSaving
        saveManager.$patch({
            isSaving: true,
            hasUnsavedChanges: true,
        });
        await nextTick();

        // Check the rendered output
        const statusText = wrapper.find(`[data-testid="${TestId.SAVE_STATUS_TEXT}"]`);
        const statusIcon = wrapper.find(`[data-testid="${TestId.SAVE_STATUS_ICON}"]`);

        expect(statusText.text()).toContain('Saving...');
        expect(statusIcon.text()).toBe('Clock');
        expect(statusText.classes()).toContain('text-blue-600');
    });

    it('shows error state when there is a save error', async () => {
        const { wrapper, saveManager } = await createWrapper();
        const errorMessage = 'Failed to save';

        // Trigger error state
        saveManager.$patch({
            saveError: errorMessage,
            hasUnsavedChanges: true,
        });
        await nextTick();

        const statusText = wrapper.find(`[data-testid="${TestId.SAVE_STATUS_TEXT}"]`);
        const statusIcon = wrapper.find(`[data-testid="${TestId.SAVE_STATUS_ICON}"]`);
        const errorElement = wrapper.find(`[data-testid="${TestId.SAVE_STATUS_ERROR}"]`);

        expect(statusText.text()).toContain('Save failed');
        expect(errorElement.exists()).toBe(true);
        expect(errorElement.text()).toContain(errorMessage);
        expect(statusIcon.text()).toBe('AlertCircle');
        expect(statusText.classes()).toContain('text-red-600');
    });

    it('shows unsaved changes state when there are unsaved changes', async () => {
        const { wrapper, saveManager } = await createWrapper();

        // Trigger unsaved changes
        saveManager.$patch({
            hasUnsavedChanges: true,
        });
        await nextTick();

        const statusText = wrapper.find(`[data-testid="${TestId.SAVE_STATUS_TEXT}"]`);
        const statusIcon = wrapper.find(`[data-testid="${TestId.SAVE_STATUS_ICON}"]`);

        expect(statusText.text()).toContain('Unsaved changes');
        expect(statusIcon.text()).toBe('Save');
        expect(statusText.classes()).toContain('text-yellow-600');
    });

    it('formats the last saved time correctly', async () => {
        const { wrapper, saveManager } = await createWrapper();
        const now = new Date();
        const fiveMinutesAgo = new Date(now.getTime() - 5 * 60 * 1000);

        // Set last saved time
        saveManager.$patch({
            lastSaved: fiveMinutesAgo,
        });
        await nextTick();

        const statusText = wrapper.find(`[data-testid="${TestId.SAVE_STATUS_TEXT}"]`);
        expect(statusText.text()).toContain('5m ago');
    });

    it('calls saveWithErrorHandling when save button is clicked', async () => {
        const { wrapper, saveManager, editorStore } = await createWrapper();

        // Set up unsaved changes
        saveManager.$patch({
            hasUnsavedChanges: true,
        });
        await nextTick();

        const saveButton = wrapper.find(`[data-testid="${TestId.SAVE_BUTTON}"]`);
        await saveButton.trigger('click');

        // Wait for the save operation to complete
        await nextTick();

        expect(editorStore.saveAllLayers).toHaveBeenCalled();
    });

    it('toggles auto-save when the auto-save button is clicked', async () => {
        const { wrapper, saveManager } = await createWrapper();

        // Initial state should be true
        expect(saveManager.autoSaveEnabled).toBe(true);

        const autoSaveButton = wrapper.find(`[data-testid="${TestId.AUTO_SAVE_TOGGLE}"]`);
        expect(autoSaveButton.text()).toContain('Auto-save: ON');

        await autoSaveButton.trigger('click');
        await nextTick();

        // Should be toggled to false
        expect(saveManager.autoSaveEnabled).toBe(false);
        expect(autoSaveButton.text()).toContain('Auto-save: OFF');
    });

    it('disables save button when saving', async () => {
        // Create a fresh instance with initial state
        const pinia = createTestingPinia({
            createSpy: vi.fn,
            stubActions: false,
            initialState: {
                saveManager: {
                    autoSaveEnabled: true,
                    hasUnsavedChanges: true, // Ensure unsaved changes exist
                    isSaving: false,
                    saveError: null,
                    lastSaved: null,
                },
            },
        });

        setActivePinia(pinia);

        // Create stores
        const saveManager = useSaveManager();
        const editorStore = useEditorStore(pinia);
        editorStore.saveAllLayers = vi.fn().mockResolvedValue({ success: true });

        // Mount the component
        const wrapper = mount(SaveStatus, {
            global: {
                plugins: [pinia],
            },
        });

        await nextTick();

        // Verify save button is visible (has unsaved changes and not currently saving)
        let saveButton = wrapper.find(`[data-testid="${TestId.SAVE_BUTTON}"]`);
        expect(saveButton.exists()).toBe(true);

        // Set saving state to true
        await saveManager.$patch({ isSaving: true });
        await nextTick();

        // Button should be disabled when saving
        saveButton = wrapper.find(`[data-testid="${TestId.SAVE_BUTTON}"]`);
        expect(saveButton.attributes('disabled')).toBeDefined();

        // Set saving state back to false
        await saveManager.$patch({ isSaving: false });
        await nextTick();

        // Button should be enabled again
        saveButton = wrapper.find(`[data-testid="${TestId.SAVE_BUTTON}"]`);
        expect(saveButton.attributes('disabled')).toBeUndefined();
    });

    it('hides save button when saving', async () => {
        // Create a fresh instance with initial state
        const pinia = createTestingPinia({
            createSpy: vi.fn,
            stubActions: false,
            initialState: {
                saveManager: {
                    autoSaveEnabled: true,
                    hasUnsavedChanges: true, // Ensure unsaved changes exist
                    isSaving: false,
                    saveError: null,
                    lastSaved: null,
                },
            },
        });

        setActivePinia(pinia);

        // Create stores
        const saveManager = useSaveManager();
        const editorStore = useEditorStore(pinia);
        editorStore.saveAllLayers = vi.fn().mockResolvedValue({ success: true });

        // Mount the component
        const wrapper = mount(SaveStatus, {
            global: {
                plugins: [pinia],
            },
        });

        await nextTick();

        // Verify save button is visible when there are unsaved changes and not saving
        let saveButton = wrapper.find(`[data-testid="${TestId.SAVE_BUTTON}"]`);
        expect(saveButton.exists(), 'Save button should exist when there are unsaved changes').toBe(true);

        // Set saving state to true
        await saveManager.$patch({ isSaving: true });
        await nextTick();

        // Button should be hidden when saving
        saveButton = wrapper.find(`[data-testid="${TestId.SAVE_BUTTON}"]`);
        expect(saveButton.exists(), 'Save button should not exist when saving').toBe(false);

        // Set saving state back to false
        await saveManager.$patch({ isSaving: false });
        await nextTick();

        // Button should be visible again
        saveButton = wrapper.find(`[data-testid="${TestId.SAVE_BUTTON}"]`);
        expect(saveButton.exists(), 'Save button should exist again after saving is complete').toBe(true);
    });

    it('hides save button when there are no unsaved changes', async () => {
        const { wrapper } = await createWrapper();
        expect(wrapper.find(`[data-testid="${TestId.SAVE_BUTTON}"]`).exists()).toBe(false);
    });

    it('shows correct auto-save button state based on store', async () => {
        // Create a fresh instance of the store with initial state
        const pinia = createTestingPinia({
            createSpy: vi.fn,
            stubActions: false,
            initialState: {
                saveManager: {
                    autoSaveEnabled: true,
                    hasUnsavedChanges: false,
                    isSaving: false,
                    saveError: null,
                    lastSaved: null,
                },
            },
        });

        setActivePinia(pinia);

        // Create stores
        const saveManager = useSaveManager();
        const editorStore = useEditorStore(pinia);

        // Mock editor store method
        editorStore.saveAllLayers = vi.fn().mockResolvedValue({ success: true });

        // Mount the component with the store
        const wrapper = mount(SaveStatus, {
            global: {
                plugins: [pinia],
            },
        });

        await nextTick();

        // Initial state should be ON by default
        expect(saveManager.autoSaveEnabled).toBe(true);
        let autoSaveButton = wrapper.find(`[data-testid="${TestId.AUTO_SAVE_TOGGLE}"]`);
        expect(autoSaveButton.text()).toContain('Auto-save: ON');

        // Change store state to disabled using the action
        await saveManager.toggleAutoSave();
        await nextTick();

        // Get fresh reference to the button after update
        autoSaveButton = wrapper.find(`[data-testid="${TestId.AUTO_SAVE_TOGGLE}"]`);

        // Verify button text and classes
        expect(autoSaveButton.text()).toContain('Auto-save: OFF');
        expect(autoSaveButton.classes()).toContain('border-gray-300');
        expect(autoSaveButton.classes()).toContain('bg-gray-50');
        expect(autoSaveButton.classes()).toContain('text-gray-700');

        // Change back to enabled using the action
        await saveManager.toggleAutoSave();
        await nextTick();

        // Verify button updates back to ON state
        autoSaveButton = wrapper.find(`[data-testid="${TestId.AUTO_SAVE_TOGGLE}"]`);
        expect(autoSaveButton.text()).toContain('Auto-save: ON');
    });

    it.skip('triggers auto-save when status changes to UNSAVED and auto-save is enabled', async () => {
        const { saveManager, editorStore } = await createWrapper();
        saveManager.autoSaveEnabled = true;
        await nextTick();

        // Trigger unsaved changes
        //saveManager.saveStatus = SaveStatusType.UNSAVED;
        await nextTick();

        // Wait for auto-save delay (2000ms)
        await vi.advanceTimersByTimeAsync(2100);

        expect(editorStore.saveAllLayers).toHaveBeenCalled();
    });

    it.skip('handles save errors properly', async () => {
        const { saveManager, editorStore } = await createWrapper();
        const error = new Error('Save failed');

        // Mock a failing save
        //editorStore.saveAllLayers.mockRejectedValueOnce(error);

        // Trigger save
        saveManager.hasUnsavedChanges = true;
        await nextTick();

        const saveButton = wrapper.find('button:contains("Save Now")');
        await saveButton.trigger('click');

        // Wait for save to complete
        await nextTick();

        expect(wrapper.text()).toContain('Save failed');
        expect(wrapper.find('.text-red-600').exists()).toBe(true);
    });
});
