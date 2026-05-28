@extends('layouts.guest')

@section('title', 'Xác nhận mật khẩu')

@section('content')
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-4">
        <h1 class="h4">Xác nhận mật khẩu</h1>
        <form method="POST" action="{{ route('password.confirm') }}" class="vstack gap-3">
            @csrf
            <div>
                <label class="form-label" for="password">Mật khẩu</label>
                <input id="password" class="form-control @error('password') is-invalid @enderror" type="password" name="password" required>
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <button class="btn btn-primary" type="submit">Xác nhận</button>
        </form>
    </div>
</div>
@endsection
