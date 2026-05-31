@extends('layouts.sinh-vien')

@section('page-title', 'Phiếu tự đánh giá')

@section('content')
<div class="table-card p-4">
    <div class="alert alert-warning mb-0">
        {{ $message ?? 'Đã hết thời hạn nộp phiếu đánh giá.' }}
    </div>
</div>
@endsection
