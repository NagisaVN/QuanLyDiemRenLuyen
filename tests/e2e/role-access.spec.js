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

test('student can open the notification center and read a notification', async ({ page }) => {
    await page.goto('/login');
    await page.locator('#login').fill('sv001');
    await page.locator('#password').fill('password');
    await page.locator('button[type="submit"]').click();

    const badge = page.locator('#student-notification-count');
    await expect(badge).toBeVisible();
    const unreadBefore = Number.parseInt(await badge.textContent(), 10);
    await page.goto('/sinh-vien/thong-bao');
    await expect(page).toHaveURL(/\/sinh-vien\/thong-bao$/);
    await expect(page.getByRole('heading', { name: 'Thông báo dành cho sinh viên' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Chưa đọc' })).toBeVisible();

    await page.locator('.content-wrapper form[action*="/thong-bao/"][action$="/read"] button').first().click();
    await expect(page).toHaveURL(/\/sinh-vien\//);
    await expect.poll(async () => Number.parseInt(await page.locator('#student-notification-count').textContent(), 10)).toBe(unreadBefore - 1);
});

test('student sees the live activity countdown and registers without a reload', async ({ page }) => {
    await page.goto('/login');
    await page.locator('#login').fill('sv001');
    await page.locator('#password').fill('password');
    await page.locator('button[type="submit"]').click();
    await page.goto('/sinh-vien/hoat-dong');

    const card = page.locator('[data-activity-card]').first();
    await expect(card).toBeVisible();
    await expect(card.locator('[data-activity-countdown]')).toContainText('Thời gian đăng ký còn lại');
    const before = Number.parseInt(await card.locator('[data-registered-count]').textContent(), 10);

    await card.locator('[data-register-button]').click();
    await expect(card.locator('[data-register-button]')).toHaveText('Đã đăng ký');
    await expect(card.locator('[data-registered-count]')).toHaveText(String(before + 1));
    await expect(page).toHaveURL(/\/sinh-vien\/hoat-dong$/);
});
