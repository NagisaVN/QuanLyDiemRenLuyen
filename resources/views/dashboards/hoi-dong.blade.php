@extends('layouts.hoi-dong')

@section('page-title', 'Bảng điều khiển Hội đồng/Khoa')

@section('content')
    @include('dashboards.partials-stats')
    <div class="info-box">
        <span class="info-box-icon bg-info"><i class="fas fa-clipboard-check"></i></span>
        <div class="info-box-content">
            <span class="info-box-text">Phiếu đã được GVCN duyệt, chờ xác nhận</span>
            <span class="info-box-number">{{ $reviewedForms }}</span>
        </div>
    </div>
@endsection
