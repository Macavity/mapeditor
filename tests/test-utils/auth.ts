import { Page, expect } from '@playwright/test';

/**
 * Logs in a user with the provided credentials
 * @param page - Playwright page object
 * @param email - User's email
 * @param password - User's password
 * @param redirectPath - Optional path to navigate to after login
 */
export async function login(page: Page, email: string, password: string, redirectPath: string = '/dashboard'): Promise<void> {
    await page.goto('/login');
    await page.getByLabel(/email/i).fill(email);
    await page.getByLabel(/password/i).fill(password);
    await page.getByRole('button', { name: /log in/i }).click();
    await page.waitForURL(`**${redirectPath}`);
}

/**
 * Logs in with test user credentials
 * @param page - Playwright page object
 * @param redirectPath - Optional path to navigate to after login
 */
export async function loginAsTestUser(page: Page, redirectPath: string = '/dashboard'): Promise<void> {
    await login(page, 'test@paneon.de', 'bull-morbid', redirectPath);
}

/**
 * Asserts that the user is logged in
 * @param page - Playwright page object
 */
export async function assertIsLoggedIn(page: Page): Promise<void> {
    await expect(page.getByText('Tester')).toBeVisible();
}

/**
 * Logs out the current user
 * @param page - Playwright page object
 */
export async function logout(page: Page): Promise<void> {
    await page.getByRole('button', { name: /user menu/i }).click();
    await page.getByRole('menuitem', { name: /log out/i }).click();
    await page.waitForURL('**/login');
}
