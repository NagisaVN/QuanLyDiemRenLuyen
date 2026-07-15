import { test, expect } from '@playwright/test';

const accounts = [
    ['admin', '/admin/dashboard'],
    ['gvcn01', '/gvcn/dashboard'],
    ['doanhoi01', '/doan-hoi/dashboard'],
    ['ctsv01', '/hoi-dong/dashboard'],
    ['sv001', '/sinh-vien/dashboard'],
];

for (const [login, dashboard] of accounts) {
    test(`${login} reaches the expected dashboard`, async ({ page }) => {
        await page.goto('/login');
        await page.locator('#login').fill(login);
        await page.locator('#password').fill('password');
        await page.locator('button[type="submit"]').click();
        await expect(page).toHaveURL(new RegExp(`${dashboard.replaceAll('/', '\\/')}$`));
        await expect(page.locator('body')).not.toContainText('403');
    });
}

test('admin can open dynamic role and permission management', async ({ page }) => {
    await page.goto('/login');
    await page.locator('#login').fill('admin');
    await page.locator('#password').fill('password');
    await page.locator('button[type="submit"]').click();
    await page.goto('/admin/roles');
    await expect(page).toHaveURL(/\/admin\/roles$/);
    await page.goto('/admin/permissions');
    await expect(page).toHaveURL(/\/admin\/permissions$/);
});
