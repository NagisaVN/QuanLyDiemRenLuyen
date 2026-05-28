@extends('layouts.doan-hoi')

@section('page-title', 'Mã QR điểm danh')

@section('content')
<div class="table-card p-4 text-center">
    <h2 class="h5">{{ $hoatDong->ten_hoat_dong }}</h2>
    <div id="qrcode" class="d-inline-block p-3 bg-white border rounded-3 my-3"></div>
    <div class="small text-secondary text-break">{{ $checkInUrl }}</div>
    <a class="btn btn-outline-secondary mt-3" href="{{ route('doan-hoi.activities.registrations', $hoatDong) }}">Quay lại điểm danh</a>
</div>
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
new QRCode(document.getElementById('qrcode'), { text: @json($checkInUrl), width: 240, height: 240 });
</script>
@endpush
@endsection
