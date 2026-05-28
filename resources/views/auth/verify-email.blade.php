@extends('layouts.guest')

@section('title', 'Xác thực email')

@section('content')
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-4">
        <h1 class="h4">Xác thực email</h1>
        <p class="text-secondary">Vui lòng kiểm tra email để xác thực tài khoản.</p>
        @if (session('status') === 'verification-link-sent')
            <div class="alert alert-success">Liên kết xác thực mới đã được gửi.</div>
        @endif
        <form method="POST" action="{{ route('verification.send') }}" class="d-inline">
            @csrf
            <button class="btn btn-primary" type="submit">Gửi lại email</button>
        </form>
        <form method="POST" action="{{ route('logout') }}" class="d-inline">
            @csrf
            <button class="btn btn-link" type="submit">Đăng xuất</button>
        </form>
    </div>
</div>
@endsection
