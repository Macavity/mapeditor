import { expect, test } from '@playwright/test';
import { assertIsLoggedIn, loginAsTestUser } from '../test-utils/auth';
import { verifyBreadcrumbs } from '../test-utils/navigation';

test.describe('Login', () => {
    test('should allow user to login with valid credentials', async ({ page }) => {
        // Navigate to the login page
        await page.goto('/login');

        // Check if we're on the login page
        await expect(page.getByRole('heading', { name: /log in/i })).toBeVisible();

        // Use the login utility
        await loginAsTestUser(page);

        // Verify successful login by checking breadcrumb
        await verifyBreadcrumbs(page, ['Dashboard']);

        // Additional verification
        await assertIsLoggedIn(page);
    });

    test('should show error with invalid credentials', async ({ page }) => {
        // Navigate to the login page
        await page.goto('/login');

        // Fill in the login form with incorrect credentials
        await page.getByLabel(/email/i).fill('test@paneon.de');
        await page.getByLabel(/password/i).fill('wrong-password');

        // Click the login button
        await page.getByRole('button', { name: /log in/i }).click();

        // Verify error message is displayed
        await expect(page.getByText(/these credentials do not match our records/i)).toBeVisible();
    });
});
