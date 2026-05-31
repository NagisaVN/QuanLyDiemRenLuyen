@extends('layouts.shell')

@section('layout-title', 'Công Tác Sinh Viên')
@section('nav')
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('hoi-dong.dashboard') ? 'active' : '' }}" href="{{ route('hoi-dong.dashboard') }}">
            <i class="nav-icon fas fa-tachometer-alt"></i>
            <p>Bảng điều khiển</p>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('hoi-dong.evaluations.*') ? 'active' : '' }}" href="{{ route('hoi-dong.evaluations.index') }}">
            <i class="nav-icon fas fa-check-double"></i>
            <p>Xác nhận điểm</p>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.dot-danh-gia.*') ? 'active' : '' }}" href="{{ route('admin.dot-danh-gia.index') }}">
            <i class="nav-icon fas fa-hourglass-half"></i>
            <p>Đợt đánh giá</p>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('hoi-dong.export.excel') ? 'active' : '' }}" href="{{ route('hoi-dong.export.excel') }}">
            <i class="nav-icon fas fa-file-excel"></i>
            <p>Xuất Excel</p>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('hoi-dong.export.pdf') ? 'active' : '' }}" href="{{ route('hoi-dong.export.pdf') }}">
            <i class="nav-icon fas fa-file-pdf"></i>
            <p>Xuất PDF</p>
        </a>
    </li>
@endsection
