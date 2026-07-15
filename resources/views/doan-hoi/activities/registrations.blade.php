@extends(auth()->user()->hasRole('admin') ? 'layouts.admin' : 'layouts.doan-hoi')

@section('page-title', 'Danh sách đăng ký')

@section('content')
<div class="d-flex justify-content-end mb-3">
    <a class="btn btn-outline-success" href="{{ route('doan-hoi.activities.qr', $hoatDong) }}">
        <i class="bi bi-qr-code me-1"></i> QR điểm danh và duyệt cộng điểm
    </a>
</div>
<div class="row g-4">
    <div class="col-xl-8">
        <div class="table-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Sinh viên</th><th>Lớp</th><th>Đăng ký lúc</th><th>Trạng thái</th></tr></thead>
                    <tbody>
                        @forelse ($registrations as $registration)
                            <tr>
                                <td>{{ $registration->sinhVien->ho_ten }}<div class="small text-secondary">{{ $registration->sinhVien->ma_sinh_vien }}</div></td>
                                <td>{{ $registration->sinhVien->lop->ten_lop }}</td>
                                <td>{{ $registration->registered_at?->timezone(config('app.display_timezone'))->format('d/m/Y H:i') ?? '-' }}</td>
                                <td><span class="badge text-bg-info">{{ config('ui.statuses.' . $registration->trang_thai, $registration->trang_thai) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-secondary py-4">Chưa có đăng ký.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">{{ $registrations->links() }}</div>
    </div>
    <div class="col-xl-4">
        <div class="table-card p-3 mb-3">
            <h2 class="h5">Điểm danh thủ công</h2>
            <form method="POST" action="{{ route('doan-hoi.activities.attendance', $hoatDong) }}" class="vstack gap-2">
                @csrf
                <input class="form-control" name="ma_sinh_vien" placeholder="Mã sinh viên">
                <button class="btn btn-primary">Điểm danh</button>
            </form>
        </div>
        <div class="table-card p-3">
            <h2 class="h5">Cộng/trừ điểm đặc biệt</h2>
            <form method="POST" action="{{ route('doan-hoi.activities.manual-adjust', $hoatDong) }}" class="vstack gap-2">
                @csrf
                <input class="form-control" name="ma_sinh_vien" placeholder="Mã sinh viên">
                <input class="form-control" type="number" name="points" placeholder="Điểm +/-">
                <textarea class="form-control" name="reason" rows="2" placeholder="Lý do"></textarea>
                <button class="btn btn-outline-primary">Ghi nhận</button>
            </form>
        </div>
    </div>
</div>
@endsection
