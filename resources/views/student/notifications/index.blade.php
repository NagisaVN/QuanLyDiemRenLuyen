@extends('layouts.sinh-vien')

@section('page-title', 'Thông báo')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h2 class="h4 mb-1">Thông báo dành cho sinh viên</h2>
        <div class="text-secondary">Theo dõi thời hạn đánh giá, hoạt động và cập nhật hệ thống.</div>
    </div>
    <form method="POST" action="{{ route('sinh-vien.notifications.read-all') }}">
        @csrf
        @method('PATCH')
        <button class="btn btn-outline-primary" type="submit"><i class="fas fa-check-double mr-1"></i> Đọc tất cả</button>
    </form>
</div>

<div class="nav nav-pills mb-3" role="navigation" aria-label="Lọc thông báo">
    @foreach (['all' => 'Tất cả', 'unread' => 'Chưa đọc', 'evaluation' => 'Đánh giá', 'system' => 'Hệ thống'] as $value => $label)
        <a class="nav-link {{ $filter === $value ? 'active' : '' }}" href="{{ route('sinh-vien.notifications.index', ['filter' => $value]) }}">{{ $label }}</a>
    @endforeach
</div>

<div class="table-card">
    @forelse ($notifications as $notification)
        @php
            $expired = $notification->isExpired();
            $icon = $notification->isEvaluation() ? 'fas fa-clipboard-check text-primary' : 'fas fa-bullhorn text-success';
        @endphp
        <div class="p-3 border-bottom d-flex align-items-start {{ ! $notification->is_read && ! $expired ? 'bg-light' : '' }}">
            <div class="rounded-circle bg-white shadow-sm d-flex align-items-center justify-content-center mr-3" style="width:44px;height:44px;flex:0 0 44px">
                <i class="{{ $icon }}"></i>
            </div>
            <div class="flex-grow-1 min-width-0">
                <div class="d-flex flex-wrap justify-content-between gap-2">
                    <h3 class="h6 mb-1">{{ $notification->title }}</h3>
                    <div>
                        @if ($expired)
                            <span class="badge text-bg-secondary">Hết hạn</span>
                        @elseif (! $notification->is_read)
                            <span class="badge text-bg-danger">Mới</span>
                        @else
                            <span class="badge text-bg-success">Đã đọc</span>
                        @endif
                    </div>
                </div>
                <div class="text-secondary mb-2" style="white-space:pre-line">{{ $notification->content }}</div>
                <div class="small text-muted mb-2" title="{{ $notification->created_at->format('d/m/Y H:i:s') }}">
                    <i class="far fa-clock mr-1"></i>{{ $notification->created_at->diffForHumans() }} · {{ $notification->created_at->timezone(config('app.display_timezone'))->format('d/m/Y H:i') }}
                </div>
                <form method="POST" action="{{ route('sinh-vien.notifications.read', $notification) }}">
                    @csrf
                    @method('PATCH')
                    <button class="btn btn-sm {{ $notification->is_read ? 'btn-outline-secondary' : 'btn-primary' }}" type="submit">
                        {{ $notification->action_url ? 'Xem chi tiết' : 'Đánh dấu đã đọc' }}
                    </button>
                </form>
            </div>
        </div>
    @empty
        <div class="p-5 text-center text-muted">
            <i class="far fa-bell-slash fa-2x mb-3"></i>
            <div>Không có thông báo phù hợp.</div>
        </div>
    @endforelse
</div>

<div class="mt-3">{{ $notifications->links() }}</div>
@endsection
