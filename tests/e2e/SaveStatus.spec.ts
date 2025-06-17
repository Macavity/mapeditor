import { expect, test } from '@playwright/test';
import { TestId } from '../../resources/js/types/TestId';
import { loginAsTestUser } from '../test-utils/auth';
import { openTestMap } from '../test-utils/maps';

test.describe('SaveStatus', () => {
    test.beforeEach(async ({ page }) => {
        // Log in and open the test map
        await loginAsTestUser(page);
        await openTestMap(page);
    });

    test('should show initial saved state', async ({ page }) => {
        // Verify the save status shows as saved initially
        const saveStatus = page.getByTestId(TestId.SAVE_STATUS);
        await expect(saveStatus).toBeVisible();

        const statusText = saveStatus.getByTestId(TestId.SAVE_STATUS_TEXT);
        await expect(statusText).toContainText(/saved/i);

        // Verify the check icon is shown
        const statusIcon = saveStatus.getByTestId(TestId.SAVE_STATUS_ICON);
        await expect(statusIcon).toBeVisible();
    });

    test('should show unsaved changes when making edits', async ({ page }) => {
        // Make an edit (e.g., draw on the canvas)
        // This is a simplified example - you might need to adjust based on actual editor implementation
        await page.evaluate(() => {
            // Simulate an edit by dispatching a custom event or directly updating the store
            window.dispatchEvent(new CustomEvent('editor:change'));
        });

        // Verify the save status shows unsaved changes
        const saveStatus = page.getByTestId(TestId.SAVE_STATUS);
        const statusText = saveStatus.getByTestId(TestId.SAVE_STATUS_TEXT);
        await expect(statusText).toContainText(/unsaved changes/i);

        // Verify the save button is visible
        const saveButton = saveStatus.getByTestId(TestId.SAVE_BUTTON);
        await expect(saveButton).toBeVisible();

        // Click save and verify status changes back to saved
        await saveButton.click();
        await expect(statusText).toContainText(/saved/i, { timeout: 5000 });
    });

    test('should toggle auto-save', async ({ page }) => {
        const saveStatus = page.getByTestId(TestId.SAVE_STATUS);
        const autoSaveToggle = saveStatus.getByTestId(TestId.AUTO_SAVE_TOGGLE);

        // Toggle auto-save off
        await autoSaveToggle.click();
        await expect(autoSaveToggle).toContainText('Auto-save: OFF');

        // Toggle auto-save back on
        await autoSaveToggle.click();
        await expect(autoSaveToggle).toContainText('Auto-save: ON');
    });
});
