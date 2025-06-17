import { Page, expect } from '@playwright/test';
import { TestId } from '../../resources/js/types/TestId';

const TEST_MAP_NAME = 'Test Map';
const DEFAULT_MAP_WIDTH = 20;
const DEFAULT_MAP_HEIGHT = 20;
const DEFAULT_TILE_SIZE = 32;

interface MapDimensions {
    width: number;
    height: number;
    tileSize: number;
}

export async function ensureTestMapExists(
    page: Page,
    dimensions: MapDimensions = {
        width: DEFAULT_MAP_WIDTH,
        height: DEFAULT_MAP_HEIGHT,
        tileSize: DEFAULT_TILE_SIZE,
    },
): Promise<void> {
    // Navigate to manage maps
    await page.goto('/manage-maps');

    // Wait for the map list to load
    await page.waitForSelector(`[data-testid="${TestId.MAP_LIST_TABLE}"]`);

    // Check if test map already exists by looking for its name in the table
    const mapExists = (await page.getByTestId(TestId.MAP_ROW_NAME).filter({ hasText: TEST_MAP_NAME }).count()) > 0;

    if (!mapExists) {
        await createTestMap(page, dimensions);
    }
}

export async function createTestMap(
    page: Page,
    dimensions: MapDimensions = {
        width: DEFAULT_MAP_WIDTH,
        height: DEFAULT_MAP_HEIGHT,
        tileSize: DEFAULT_TILE_SIZE,
    },
): Promise<void> {
    // Click new map button
    await page.getByRole('button', { name: /new map/i }).click();
    const dialog = page.getByRole('dialog');

    // Fill in map details
    await dialog.getByTestId(TestId.MAP_NAME_INPUT).fill(TEST_MAP_NAME);
    await dialog.getByTestId(TestId.MAP_WIDTH_INPUT).fill(dimensions.width.toString());
    await dialog.getByTestId(TestId.MAP_HEIGHT_INPUT).fill(dimensions.height.toString());
    await dialog.getByTestId(TestId.MAP_TILE_SIZE_INPUT).fill(dimensions.tileSize.toString());

    // Submit the form
    await dialog.getByTestId(TestId.CREATE_MAP_SUBMIT).click();

    // Wait for the editor to load and verify we're there
    await page.waitForURL('**/edit');
    await expect(page).toHaveURL(/.*\/edit$/);
}

export async function openTestMap(page: Page): Promise<void> {
    // Navigate to manage maps
    await page.goto('/manage-maps');

    // Wait for the map list to load
    await page.waitForSelector(`[data-testid="${TestId.MAP_LIST_TABLE}"]`);

    // Find the test map row by its name
    const mapRow = page.getByTestId(TestId.MAP_ROW_NAME).filter({ hasText: TEST_MAP_NAME });
    await expect(mapRow).toBeVisible();

    // Get the map's UUID from the parent row's test ID
    const mapRowElement = await mapRow.locator('xpath=ancestor::tr').first();
    const mapUuid = await mapRowElement.getAttribute('data-testid').then((id) => id?.split('-')[1]);

    if (!mapUuid) {
        throw new Error(`Test map "${TEST_MAP_NAME}" not found in the map list`);
    }

    // Click the edit button for this specific map
    const editButton = page.getByTestId(`${TestId.MAP_ROW_EDIT_BUTTON}-${mapUuid}`);
    await expect(editButton).toBeVisible();
    await editButton.click();

    // Wait for the editor to load and verify we're there
    await page.waitForURL('**/edit');
    await expect(page).toHaveURL(/.*\/edit$/);
}

export async function deleteTestMap(page: Page): Promise<void> {
    // Navigate to manage maps
    await page.goto('/manage-maps');

    // Wait for the map list to load
    await page.waitForSelector(`[data-testid="${TestId.MAP_LIST_TABLE}"]`);

    // Find the test map row
    const mapRow = page.getByTestId(TestId.MAP_ROW_NAME).filter({ hasText: TEST_MAP_NAME });
    const mapRowElement = await mapRow.locator('xpath=ancestor::tr').first();
    const mapUuid = await mapRowElement.getAttribute('data-testid').then((id) => id?.split('-')[1]);

    if (!mapUuid) {
        // Map doesn't exist, nothing to delete
        return;
    }

    // Click delete button and confirm
    const deleteButton = page.getByTestId(`${TestId.MAP_ROW_DELETE_BUTTON}-${mapUuid}`);
    await deleteButton.click();

    // Handle the confirmation dialog
    page.on('dialog', (dialog) => dialog.accept());

    // Wait for the map to be removed from the list
    await expect(mapRow).not.toBeVisible();
}

export async function getTestMapUuid(page: Page): Promise<string> {
    // Navigate to manage maps
    await page.goto('/manage-maps');

    // Wait for the map list to load
    await page.waitForSelector(`[data-testid="${TestId.MAP_LIST_TABLE}"]`);

    // Find the test map row
    const mapRow = page.getByTestId(TestId.MAP_ROW_NAME).filter({ hasText: TEST_MAP_NAME });
    const mapRowElement = await mapRow.locator('xpath=ancestor::tr').first();
    const mapUuid = await mapRowElement.getAttribute('data-testid').then((id) => id?.split('-')[1]);

    if (!mapUuid) {
        throw new Error('Could not find test map UUID');
    }

    return mapUuid;
}
