@extends('layouts.admin')

@section('page-title', 'Bảng điều khiển quản trị viên')

@section('content')
    @include('dashboards.partials-stats')
    <div class="callout callout-info table-card p-3">
        <h2 class="h5 mb-2"><i class="fas fa-cogs mr-2 text-info"></i>Quản trị hệ thống</h2>
        <p class="text-secondary mb-0">Quản lý người dùng, phân quyền, khoa, lớp, học kỳ, tiêu chí, hoạt động, thông báo, nhật ký và sao lưu dữ liệu.</p>
    </div>
@endsection
