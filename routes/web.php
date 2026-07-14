<?php

use App\Http\Controllers\Admin\CrudController;
use App\Http\Controllers\Admin\DotDanhGiaController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DoanHoi\ActivityController as DoanHoiActivityController;
use App\Http\Controllers\Gvcn\EvaluationController as GvcnEvaluationController;
use App\Http\Controllers\HoiDong\EvaluationController as HoiDongEvaluationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Student\ActivityController as StudentActivityController;
use App\Http\Controllers\Student\EvaluationController as StudentEvaluationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'home']);

Route::get('/dashboard', [DashboardController::class, 'redirect'])->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'role:admin|hoi_dong_khoa'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dot-danh-gia', [DotDanhGiaController::class, 'index'])
            ->middleware('permission:manage_dot_danh_gia')
            ->name('dot-danh-gia.index');
        Route::get('/dot-danh-gia/create', [DotDanhGiaController::class, 'create'])
            ->middleware('permission:manage_dot_danh_gia')
            ->name('dot-danh-gia.create');
        Route::post('/dot-danh-gia', [DotDanhGiaController::class, 'store'])
            ->middleware('permission:manage_dot_danh_gia')
            ->name('dot-danh-gia.store');
        Route::get('/dot-danh-gia/{dotDanhGia}/edit', [DotDanhGiaController::class, 'edit'])
            ->middleware('permission:manage_dot_danh_gia')
            ->name('dot-danh-gia.edit');
        Route::put('/dot-danh-gia/{dotDanhGia}', [DotDanhGiaController::class, 'update'])
            ->middleware('permission:manage_dot_danh_gia')
            ->name('dot-danh-gia.update');
        Route::delete('/dot-danh-gia/{dotDanhGia}', [DotDanhGiaController::class, 'destroy'])
            ->middleware('permission:manage_dot_danh_gia')
            ->name('dot-danh-gia.destroy');
        Route::get('/dot-danh-gia/{dotDanhGia}/ket-qua', [DotDanhGiaController::class, 'results'])
            ->middleware('permission:manage_dot_danh_gia')
            ->name('dot-danh-gia.results');
        Route::get('/dot-danh-gia/{dotDanhGia}/export', [DotDanhGiaController::class, 'exportExcel'])
            ->middleware('permission:export reports')
            ->name('dot-danh-gia.export');
    });

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'admin'])->name('dashboard');
    Route::get('/{module}', [CrudController::class, 'index'])->name('crud.index');
    Route::get('/{module}/create', [CrudController::class, 'create'])->name('crud.create');
    Route::post('/{module}', [CrudController::class, 'store'])->name('crud.store');
    Route::get('/{module}/{id}', [CrudController::class, 'show'])->name('crud.show');
    Route::get('/{module}/{id}/edit', [CrudController::class, 'edit'])->name('crud.edit');
    Route::put('/{module}/{id}', [CrudController::class, 'update'])->name('crud.update');
    Route::delete('/{module}/{id}', [CrudController::class, 'destroy'])->name('crud.destroy');
});

Route::middleware(['auth', 'role:sinh_vien'])->prefix('sinh-vien')->name('sinh-vien.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'sinhVien'])->name('dashboard');
    Route::get('/phieu-danh-gia', [StudentEvaluationController::class, 'index'])->name('evaluations.index');
    Route::put('/phieu-danh-gia', [StudentEvaluationController::class, 'update'])->middleware('student.evaluation.period')->name('evaluations.update');
    Route::post('/phieu-danh-gia/submit', [StudentEvaluationController::class, 'submit'])->middleware('student.evaluation.period')->name('evaluations.submit');
    Route::post('/phieu-danh-gia/minh-chung', [StudentEvaluationController::class, 'upload'])->middleware('student.evaluation.period')->name('evaluations.upload');
    Route::get('/phieu-danh-gia/lich-su', [StudentEvaluationController::class, 'history'])->name('evaluations.history');
    Route::get('/phieu-danh-gia/in', [StudentEvaluationController::class, 'print'])->name('evaluations.print');
    Route::get('/hoat-dong', [StudentActivityController::class, 'index'])->name('activities.index');
    Route::post('/hoat-dong/{hoatDong}/dang-ky', [StudentActivityController::class, 'register'])->name('activities.register');
    Route::get('/hoat-dong/{hoatDong}/check-in', [StudentActivityController::class, 'checkIn'])->name('activities.check-in');
    Route::get('/diem-danh/scan', [StudentActivityController::class, 'scan'])->name('attendance.scan');
});

Route::middleware(['auth', 'role:admin|can_bo_doan_hoi'])->prefix('api/attendance')->name('api.attendance.')->group(function () {
    Route::post('/sessions', [AttendanceController::class, 'storeSession'])->name('sessions.store');
    Route::post('/approve/{hoatDong}', [AttendanceController::class, 'approve'])->name('approve');
});

Route::middleware(['auth', 'role:sinh_vien'])->prefix('api/attendance')->name('api.attendance.')->group(function () {
    Route::post('/scan', [AttendanceController::class, 'scan'])->name('scan');
});

Route::get('/minh-chung/{minhChung}/download', [StudentEvaluationController::class, 'download'])
    ->middleware('auth')
    ->name('minh-chung.download');

Route::middleware(['auth', 'role:gvcn'])->prefix('gvcn')->name('gvcn.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'gvcn'])->name('dashboard');
    Route::get('/phieu-danh-gia', [GvcnEvaluationController::class, 'index'])->name('evaluations.index');
    Route::get('/phieu-danh-gia/{phieu}', [GvcnEvaluationController::class, 'show'])->name('evaluations.show');
    Route::put('/phieu-danh-gia/{phieu}', [GvcnEvaluationController::class, 'update'])->middleware('gvcn.review.period')->name('evaluations.update');
    Route::match(['post', 'put'], '/phieu-danh-gia/{phieu}/xac-nhan', [GvcnEvaluationController::class, 'confirm'])->middleware('gvcn.review.period')->name('evaluations.confirm');
    Route::post('/minh-chung/{minhChung}/duyet', [GvcnEvaluationController::class, 'reviewEvidence'])->middleware('gvcn.review.period')->name('evidence.review');
});

Route::middleware(['auth', 'role:can_bo_doan_hoi'])->prefix('doan-hoi')->name('doan-hoi.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'doanHoi'])->name('dashboard');
    Route::resource('activities', DoanHoiActivityController::class)->except(['show'])->parameters(['activities' => 'hoatDong']);
    Route::get('/activities/{hoatDong}/registrations', [DoanHoiActivityController::class, 'registrations'])->name('activities.registrations');
    Route::post('/registrations/{registration}/approve', [DoanHoiActivityController::class, 'approve'])->name('registrations.approve');
    Route::post('/activities/{hoatDong}/attendance', [DoanHoiActivityController::class, 'attendance'])->name('activities.attendance');
    Route::get('/activities/{hoatDong}/qr', [DoanHoiActivityController::class, 'qr'])->name('activities.qr');
    Route::post('/activities/{hoatDong}/manual-adjust', [DoanHoiActivityController::class, 'manualAdjust'])->name('activities.manual-adjust');
});

Route::middleware(['auth', 'role:hoi_dong_khoa'])->prefix('hoi-dong')->name('hoi-dong.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'hoiDong'])->name('dashboard');
    Route::get('/phieu-danh-gia', [HoiDongEvaluationController::class, 'index'])->name('evaluations.index');
    Route::get('/phieu-danh-gia/{phieu}', [HoiDongEvaluationController::class, 'show'])->name('evaluations.show');
    Route::put('/phieu-danh-gia/{phieu}', [HoiDongEvaluationController::class, 'update'])->name('evaluations.update');
    Route::match(['post', 'put'], '/phieu-danh-gia/{phieu}/xac-nhan', [HoiDongEvaluationController::class, 'approve'])->name('evaluations.approve');
    Route::post('/phieu-danh-gia/{phieu}/khoa', [HoiDongEvaluationController::class, 'lock'])->name('evaluations.lock');
    Route::get('/export', [HoiDongEvaluationController::class, 'exportIndex'])->name('export.index');
    Route::get('/export/excel', [HoiDongEvaluationController::class, 'exportExcel'])->name('export.excel');
    Route::get('/export/pdf', [HoiDongEvaluationController::class, 'exportPdf'])->name('export.pdf');
});

require __DIR__.'/auth.php';
