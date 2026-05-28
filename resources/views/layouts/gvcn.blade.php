@extends('layouts.shell')

@section('layout-title', 'GVCN/Cố vấn')
@section('nav')
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('gvcn.dashboard') ? 'active' : '' }}" href="{{ route('gvcn.dashboard') }}">
            <i class="nav-icon fas fa-tachometer-alt"></i>
            <p>Bảng điều khiển</p>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('gvcn.evaluations.*') ? 'active' : '' }}" href="{{ route('gvcn.evaluations.index') }}">
            <i class="nav-icon fas fa-users"></i>
            <p>Phiếu lớp phụ trách</p>
        </a>
    </li>
@endsection
