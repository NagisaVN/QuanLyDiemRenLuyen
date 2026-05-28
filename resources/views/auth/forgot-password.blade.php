@extends('layouts.guest')

@section('title', 'Quên mật khẩu')

@section('content')
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-4">
        <h1 class="h4">Quên mật khẩu</h1>
        <p class="text-secondary">Nhập email tài khoản để nhận liên kết đặt lại mật khẩu.</p>
        @if (session('status'))
            <div class="alert alert-success">{{ config('ui.messages.' . session('status'), session('status')) }}</div>
        @endif
        <form method="POST" action="{{ route('password.email') }}" class="vstack gap-3">
            @csrf
            <div>
                <label class="form-label" for="email">Email</label>
                <input id="email" class="form-control @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email') }}" required autofocus>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <button class="btn btn-primary" type="submit">Gửi liên kết đặt lại mật khẩu</button>
            <a href="{{ route('login') }}" class="small">Quay lại đăng nhập</a>
        </form>
    </div>
</div>
@endsection
