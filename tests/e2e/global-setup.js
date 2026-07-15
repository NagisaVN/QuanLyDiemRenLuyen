import { execFileSync } from 'node:child_process';

export default async function globalSetup() {
    const database = process.env.DB_DATABASE || 'quan_ly_diem_ren_luyen_testing';
    if ((process.env.APP_ENV || 'testing') !== 'testing' || !database.endsWith('_testing')) {
        throw new Error(`Refusing to reset unsafe database [${database}].`);
    }

    const env = {
        ...process.env,
        APP_ENV: 'testing',
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

    execFileSync(
        'D:\\laragon\\bin\\php\\php-8.3.28-Win32-vs16-x64\\php.exe',
        ['artisan', 'migrate:fresh', '--seed', '--force'],
        { cwd: process.cwd(), env, stdio: 'inherit' },
    );
}
