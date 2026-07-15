<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('content');
            $table->string('type', 50);
            $table->string('related_type', 100)->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->string('action_url', 2048)->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->string('dedupe_key', 191);
            $table->timestamps();

            $table->unique(['user_id', 'dedupe_key'], 'notifications_user_dedupe_unique');
            $table->index(['user_id', 'is_read', 'created_at'], 'notifications_user_unread_time_idx');
            $table->index(['user_id', 'type'], 'notifications_user_type_idx');
            $table->index(['related_type', 'related_id'], 'notifications_related_idx');
        });

        Schema::table('thong_baos', function (Blueprint $table): void {
            $table->timestamp('distributed_at')->nullable()->after('is_active');
            $table->index(['is_active', 'published_at', 'distributed_at'], 'thong_baos_distribution_idx');
        });

        DB::table('thong_baos')
            ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->update(['distributed_at' => now()]);

        foreach (['view student notifications', 'manage notifications'] as $permission) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $permission,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $defaultAssignments = [
            'sinh_vien' => ['view student notifications'],
            'admin' => ['view student notifications', 'manage notifications'],
        ];

        foreach ($defaultAssignments as $roleName => $permissionNames) {
            $roleId = DB::table('roles')->where('name', $roleName)->where('guard_name', 'web')->value('id');
            if (! $roleId) {
                continue;
            }

            $permissionIds = DB::table('permissions')->whereIn('name', $permissionNames)->where('guard_name', 'web')->pluck('id');
            foreach ($permissionIds as $permissionId) {
                DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permissionId, 'role_id' => $roleId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('thong_baos', function (Blueprint $table): void {
            $table->dropIndex('thong_baos_distribution_idx');
            $table->dropColumn('distributed_at');
        });

        Schema::dropIfExists('notifications');

        // Giữ lại permission nếu đã được quản trị viên gán cho role tùy chỉnh.
    }
};
