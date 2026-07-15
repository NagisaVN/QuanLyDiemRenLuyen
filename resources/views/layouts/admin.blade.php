@extends('layouts.shell')

@section('layout-title', 'Quản trị viên')
@section('nav')
    @php
        $currentModule = request()->route('module');
        $modules = [
            'users' => ['label' => 'Người dùng', 'icon' => 'fas fa-users'],
            'roles' => ['label' => 'Vai trò', 'icon' => 'fas fa-user-shield'],
            'permissions' => ['label' => 'Phân quyền', 'icon' => 'fas fa-key'],
            'khoas' => ['label' => 'Khoa', 'icon' => 'fas fa-university'],
            'lops' => ['label' => 'Lớp', 'icon' => 'fas fa-chalkboard'],
            'sinh-viens' => ['label' => 'Sinh viên', 'icon' => 'fas fa-user-graduate'],
            'nam-hocs' => ['label' => 'Năm học', 'icon' => 'fas fa-calendar-alt'],
            'hoc-kys' => ['label' => 'Học kỳ', 'icon' => 'fas fa-calendar-check'],
            'tieu-chis' => ['label' => 'Tiêu chí', 'icon' => 'fas fa-list-ol'],
            'muc-tieu-chis' => ['label' => 'Mức tiêu chí', 'icon' => 'fas fa-layer-group'],
            'minh-chungs' => ['label' => 'Minh chứng', 'icon' => 'fas fa-paperclip'],
            'thong-baos' => ['label' => 'Thông báo', 'icon' => 'fas fa-bell'],
            'logs' => ['label' => 'Nhật ký hệ thống', 'icon' => 'fas fa-history'],
            'backups' => ['label' => 'Sao lưu dữ liệu', 'icon' => 'fas fa-database'],
        ];
    @endphp

    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
            <i class="nav-icon fas fa-tachometer-alt"></i>
            <p>Bảng điều khiển</p>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.dot-danh-gia.*') ? 'active' : '' }}" href="{{ route('admin.dot-danh-gia.index') }}">
            <i class="nav-icon fas fa-hourglass-half"></i>
            <p>Đợt đánh giá</p>
        </a>
    </li>
    @can('manage activities')
        <li class="nav-item">
            <a class="nav-link" href="{{ route('doan-hoi.activities.index') }}">
                <i class="nav-icon fas fa-calendar-day"></i><p>Hoạt động</p>
            </a>
        </li>
    @endcan
    @foreach ($modules as $module => $item)
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.crud.*') && $currentModule === $module ? 'active' : '' }}" href="{{ route('admin.crud.index', $module) }}">
                <i class="nav-icon {{ $item['icon'] }}"></i>
                <p>{{ $item['label'] }}</p>
            </a>
        </li>
    @endforeach
@endsection
