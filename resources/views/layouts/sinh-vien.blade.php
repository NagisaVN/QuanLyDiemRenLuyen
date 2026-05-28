@extends('layouts.shell')

@section('layout-title', 'Sinh viên')
@section('nav')
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('sinh-vien.dashboard') ? 'active' : '' }}" href="{{ route('sinh-vien.dashboard') }}">
            <i class="nav-icon fas fa-tachometer-alt"></i>
            <p>Bảng điều khiển</p>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('sinh-vien.evaluations.index') ? 'active' : '' }}" href="{{ route('sinh-vien.evaluations.index') }}">
            <i class="nav-icon fas fa-clipboard-check"></i>
            <p>Tự đánh giá</p>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('sinh-vien.activities.*') ? 'active' : '' }}" href="{{ route('sinh-vien.activities.index') }}">
            <i class="nav-icon fas fa-calendar-day"></i>
            <p>Hoạt động</p>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('sinh-vien.evaluations.history') ? 'active' : '' }}" href="{{ route('sinh-vien.evaluations.history') }}">
            <i class="nav-icon fas fa-history"></i>
            <p>Lịch sử điểm</p>
        </a>
    </li>
@endsection
