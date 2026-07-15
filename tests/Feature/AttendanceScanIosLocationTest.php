<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AttendanceScanIosLocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_scan_page_uses_secure_context_check_and_accuracy_watcher_for_ios(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permission = Permission::findOrCreate('self evaluate', 'web');
        $student = User::factory()->create(['is_active' => true]);
        $student->givePermissionTo($permission);

        $this->actingAs($student)
            ->get(route('sinh-vien.attendance.scan', [
                'sessionId' => 1,
                'token' => 'test-token',
            ]))
            ->assertOk()
            ->assertSee('Dành cho iPhone/iPad')
            ->assertSee('Vị trí chính xác')
            ->assertSee('window.isSecureContext', false)
            ->assertSee('navigator.geolocation.watchPosition', false)
            ->assertSee('requiredAccuracy = 100', false);
    }
}
