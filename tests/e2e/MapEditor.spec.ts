import { expect, test } from '@playwright/test';
import { loginAsTestUser } from '../test-utils/auth';
import { navigateAndVerifyBreadcrumbs, verifyBreadcrumbs } from '../test-utils/navigation';

test.describe('Map Editor', () => {
    test.beforeEach(async ({ page }) => {
        // Log in before each test
        await loginAsTestUser(page);
    });

    test('should navigate to manage maps and create a new map', async ({ page }) => {
        // Navigate to manage maps and verify breadcrumbs
        await navigateAndVerifyBreadcrumbs(page, '/manage-maps', ['Maps']);

        // Click the new map button
        const newMapButton = page.getByRole('button', { name: /new map/i });
        await expect(newMapButton).toBeVisible();
        await newMapButton.click();

        // Fill in the map details in the dialog
        const dialog = page.getByRole('dialog');
        await expect(dialog).toBeVisible();

        // Fill in the map name
        const mapName = 'Test Map ' + Date.now();
        await dialog.getByTestId('map-name-input').fill(mapName);

        // Fill in dimensions
        await dialog.getByTestId('map-width-input').fill('20');
        await dialog.getByTestId('map-height-input').fill('20');
        await dialog.getByTestId('map-tile-size-input').fill('32');

        // Submit the form
        await dialog.getByTestId('create-map-submit').click();

        // Wait for the editor to load and verify breadcrumbs
        await page.waitForURL('**/edit');
        await verifyBreadcrumbs(page, ['Maps', 'Map Editor']);

        // Verify we're in the editor with the correct map name
        const heading = page.getByRole('heading', { level: 1 });
        await expect(heading).toContainText(mapName);

        // Verify the canvas is present
        const canvas = page.locator('canvas').first();
        await expect(canvas).toBeVisible();
    });
});
