@extends('layouts.guest')

@section('title', 'Đăng ký')

@section('content')
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-4">
        <h1 class="h4 mb-1">Đăng ký tài khoản</h1>
        <p class="text-secondary mb-4">Tạo tài khoản để sử dụng hệ thống quản lý điểm rèn luyện.</p>

        <form method="POST" action="{{ route('register') }}" class="vstack gap-3">
            @csrf

            <div>
                <label class="form-label" for="name">Họ tên</label>
                <input id="name" class="form-control @error('name') is-invalid @enderror" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div>
                <label class="form-label" for="email">Email</label>
                <input id="email" class="form-control @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div>
                <label class="form-label" for="password">Mật khẩu</label>
                <input id="password" class="form-control @error('password') is-invalid @enderror" type="password" name="password" required autocomplete="new-password">
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div>
                <label class="form-label" for="password_confirmation">Xác nhận mật khẩu</label>
                <input id="password_confirmation" class="form-control @error('password_confirmation') is-invalid @enderror" type="password" name="password_confirmation" required autocomplete="new-password">
                @error('password_confirmation')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <button class="btn btn-primary w-100" type="submit">Đăng ký</button>
            <a class="small text-center" href="{{ route('login') }}">Đã có tài khoản? Đăng nhập</a>
        </form>
    </div>
</div>
@endsection
