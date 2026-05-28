@extends('layouts.shell')

@section('layout-title', 'Tài khoản')
@section('page-title', isset($header) ? trim(strip_tags((string) $header)) : 'Tài khoản')
@section('nav')
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
            <i class="nav-icon fas fa-tachometer-alt"></i>
            <p>Bảng điều khiển</p>
        </a>
    </li>
@endsection
@section('content')
    {{ $slot ?? '' }}
@endsection
