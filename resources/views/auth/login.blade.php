@extends('layouts.guest')

@section('title', 'Đăng nhập')

@section('content')
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-4">
        <div class="text-center mb-4">
            <div class="display-6 text-primary"><i class="bi bi-mortarboard"></i></div>
            <h1 class="h4 mb-1">Quản lý điểm rèn luyện</h1>
            <p class="text-secondary mb-0">Đăng nhập bằng mã sinh viên hoặc email trường</p>
        </div>

        @if (session('status'))
            <div class="alert alert-success">{{ config('ui.messages.' . session('status'), session('status')) }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="vstack gap-3">
            @csrf
            <div>
                <label class="form-label" for="login">Mã sinh viên hoặc email</label>
                <input id="login" class="form-control @error('login') is-invalid @enderror" name="login" value="{{ old('login') }}" required autofocus>
                @error('login')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div>
                <label class="form-label" for="password">Mật khẩu</label>
                <input id="password" class="form-control @error('password') is-invalid @enderror" type="password" name="password" required>
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label" for="remember">Ghi nhớ</label>
                </div>
                <a href="{{ route('password.request') }}" class="small">Quên mật khẩu?</a>
            </div>
            <button class="btn btn-primary w-100" type="submit">Đăng nhập</button>
        </form>
    </div>
</div>
@endsection
