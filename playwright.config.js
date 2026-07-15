import { defineConfig } from '@playwright/test';

const database = process.env.DB_DATABASE || 'quan_ly_diem_ren_luyen_testing';

if ((process.env.APP_ENV || 'testing') !== 'testing' || !database.endsWith('_testing')) {
    throw new Error(`Unsafe E2E database [${database}]. APP_ENV must be testing and DB_DATABASE must end with _testing.`);
}

const testEnv = {
    ...process.env,
    APP_ENV: 'testing',
    APP_DEBUG: 'true',
    APP_URL: 'http://127.0.0.1:8010',
    DB_CONNECTION: 'mysql',
    DB_HOST: process.env.DB_HOST || '127.0.0.1',
    DB_PORT: process.env.DB_PORT || '3306',
    DB_DATABASE: database,
    DB_USERNAME: process.env.DB_USERNAME || 'root',
    DB_PASSWORD: process.env.DB_PASSWORD || '',
    CACHE_STORE: 'array',
    SESSION_DRIVER: 'database',
    QUEUE_CONNECTION: 'sync',
    BROADCAST_CONNECTION: 'null',
};

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: false,
    workers: 1,
    globalSetup: './tests/e2e/global-setup.js',
    use: {
        baseURL: 'http://127.0.0.1:8010',
        channel: 'chrome',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    webServer: {
        command: '"D:\\laragon\\bin\\php\\php-8.3.28-Win32-vs16-x64\\php.exe" artisan serve --host=127.0.0.1 --port=8010',
        url: 'http://127.0.0.1:8010/health',
        reuseExistingServer: false,
        env: testEnv,
        timeout: 30_000,
    },
});
