@extends('layouts.gvcn')

@section('page-title', 'Bảng điều khiển GVCN')

@section('content')
    @include('dashboards.partials-stats')
    <div class="row g-3">
        <div class="col-md-6">
            <div class="info-box">
                <span class="info-box-icon bg-primary"><i class="fas fa-chalkboard-teacher"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Lớp phụ trách</span>
                    <span class="info-box-number">{{ $lopCount }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="info-box">
                <span class="info-box-icon bg-warning"><i class="fas fa-tasks"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Phiếu lớp chờ duyệt</span>
                    <span class="info-box-number">{{ $pendingClassForms }}</span>
                </div>
            </div>
        </div>
    </div>
@endsection
