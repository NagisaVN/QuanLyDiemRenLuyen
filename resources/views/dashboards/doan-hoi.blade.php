@extends('layouts.doan-hoi')

@section('page-title', 'Bảng điều khiển Đoàn - Hội')

@section('content')
    @include('dashboards.partials-stats')
    <div class="info-box">
        <span class="info-box-icon bg-success"><i class="fas fa-user-check"></i></span>
        <div class="info-box-content">
            <span class="info-box-text">Đăng ký hoạt động chờ duyệt</span>
            <span class="info-box-number">{{ $pendingRegistrations }}</span>
        </div>
    </div>
@endsection
