@extends('layouts.guest')

@section('title', 'Đặt lại mật khẩu')

@section('content')
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-4">
        <h1 class="h4">Đặt lại mật khẩu</h1>
        <form method="POST" action="{{ route('password.store') }}" class="vstack gap-3">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">
            <div>
                <label class="form-label" for="email">Email</label>
                <input id="email" class="form-control @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div>
                <label class="form-label" for="password">Mật khẩu mới</label>
                <input id="password" class="form-control @error('password') is-invalid @enderror" type="password" name="password" required>
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div>
                <label class="form-label" for="password_confirmation">Xác nhận mật khẩu</label>
                <input id="password_confirmation" class="form-control" type="password" name="password_confirmation" required>
            </div>
            <button class="btn btn-primary" type="submit">Cập nhật mật khẩu</button>
        </form>
    </div>
</div>
@endsection
