import { expect, type Page } from '@playwright/test';

/**
 * Verifies the current page's breadcrumbs
 * @param page - Playwright page object
 * @param expectedBreadcrumbs - Array of breadcrumb texts to verify (in order)
 */
export async function verifyBreadcrumbs(
  page: Page,
  expectedBreadcrumbs: string[]
): Promise<void> {
  const breadcrumb = page.getByRole('navigation', { name: 'breadcrumb' });
  await expect(breadcrumb).toBeVisible();
  
  // Get all breadcrumb items
  const breadcrumbItems = breadcrumb.getByRole('listitem');
  const count = await breadcrumbItems.count();
  
  // Verify the number of breadcrumbs matches
  expect(count).toBe(expectedBreadcrumbs.length);
  
  // Verify each breadcrumb text
  for (let i = 0; i < expectedBreadcrumbs.length; i++) {
    const item = breadcrumbItems.nth(i);
    await expect(item).toContainText(expectedBreadcrumbs[i]);
  }
}

/**
 * Navigates to a page and verifies the breadcrumbs
 * @param page - Playwright page object
 * @param url - URL to navigate to
 * @param expectedBreadcrumbs - Array of breadcrumb texts to verify (in order)
 */
export async function navigateAndVerifyBreadcrumbs(
  page: Page,
  url: string,
  expectedBreadcrumbs: string[]
): Promise<void> {
  await page.goto(url);
  await verifyBreadcrumbs(page, expectedBreadcrumbs);
}
