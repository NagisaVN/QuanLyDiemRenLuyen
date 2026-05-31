<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 6px; }
        th { background: #f0f0f0; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <h2>Phiếu đánh giá điểm rèn luyện</h2>
    <p><strong>Sinh viên:</strong> {{ $phieu->sinhVien->ho_ten }} - {{ $phieu->sinhVien->ma_sinh_vien }}</p>
    <p><strong>Lớp:</strong> {{ $phieu->sinhVien->lop->ten_lop }} | <strong>Học kỳ:</strong> {{ $phieu->hocKy->ten_hoc_ky }}</p>
    @if ($phieu->dotDanhGia)
        <p><strong>Đợt đánh giá:</strong> {{ $phieu->dotDanhGia->ten_dot }}</p>
    @endif

    <table>
        <thead>
            <tr>
                <th>Tiêu chí</th>
                <th>Tối đa</th>
                <th>Tự chấm</th>
                <th>GVCN</th>
                <th>Công Tác Sinh Viên</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($phieu->chiTietDanhGias as $detail)
                @php
                    $gvcnScore = $detail->diem_gvcn ?? $detail->diem_tu_cham;
                    $ctsvScore = $detail->diem_hoi_dong ?? $gvcnScore;
                @endphp
                <tr>
                    <td>{{ $detail->tieuChi->ten_tieu_chi }}</td>
                    <td class="text-center">{{ $detail->tieuChi->diem_toi_da }}</td>
                    <td class="text-center">{{ $detail->diem_tu_cham }}</td>
                    <td class="text-center">{{ $gvcnScore }}</td>
                    <td class="text-center">{{ $ctsvScore }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h3>Tổng điểm: {{ $phieu->diem_cuoi ?? $phieu->diem_hoi_dong ?? $phieu->diem_gvcn ?? $phieu->diem_tu_cham }}/100</h3>
    <p><strong>Xếp loại:</strong> {{ $phieu->xep_loai }}</p>
</body>
</html>
