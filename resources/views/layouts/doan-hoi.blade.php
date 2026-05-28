@extends('layouts.shell')

@section('layout-title', 'Đoàn - Hội')
@section('nav')
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('doan-hoi.dashboard') ? 'active' : '' }}" href="{{ route('doan-hoi.dashboard') }}">
            <i class="nav-icon fas fa-tachometer-alt"></i>
            <p>Bảng điều khiển</p>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('doan-hoi.activities.*') ? 'active' : '' }}" href="{{ route('doan-hoi.activities.index') }}">
            <i class="nav-icon fas fa-calendar-check"></i>
            <p>Hoạt động</p>
        </a>
    </li>
@endsection
