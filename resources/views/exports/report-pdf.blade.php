<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 5px; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>
    <h2>Báo cáo điểm rèn luyện</h2>
    @if(isset($hocKy))
        <p><strong>Học kỳ:</strong> {{ $hocKy->ten_hoc_ky }} - <strong>Năm học:</strong> {{ $hocKy->namHoc->ten_nam_hoc ?? '' }}</p>
    @endif
    <table>
        <thead>
            <tr>
                <th>Mã SV</th><th>Họ tên</th><th>Lớp</th><th>Khoa</th><th>Học kỳ</th><th>Năm học</th><th>Điểm</th><th>Xếp loại</th><th>Trạng thái</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($forms as $form)
                <tr>
                    <td>{{ $form->sinhVien->ma_sinh_vien }}</td>
                    <td>{{ $form->sinhVien->ho_ten }}</td>
                    <td>{{ $form->sinhVien->lop->ten_lop }}</td>
                    <td>{{ $form->sinhVien->lop->khoa->ten_khoa }}</td>
                    <td>{{ $form->hocKy->ten_hoc_ky }}</td>
                    <td>{{ $form->hocKy->namHoc->ten_nam_hoc ?? '' }}</td>
                    <td>{{ $form->diem_cuoi }}</td>
                    <td>{{ $form->xep_loai }}</td>
                    <td>{{ config('ui.statuses.' . $form->trang_thai, $form->trang_thai) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
