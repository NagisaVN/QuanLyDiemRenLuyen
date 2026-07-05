<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 4px; vertical-align: top; }
        th { background: #f0f0f0; text-align: center; }
        .no-border td { border: 0; }
        .center { text-align: center; }
        .bold { font-weight: 700; }
        .section { background: #dbeafe; font-weight: 700; }
        .heading { background: #f7f7f7; font-weight: 700; }
        .total { font-weight: 700; }
        .signatures td { height: 72px; text-align: center; font-weight: 700; }
        .small { font-size: 9px; color: #333; }
    </style>
</head>
<body>
@php
    $scoreValue = function ($detail, array $fields): mixed {
        foreach ($fields as $field) {
            if ($detail && $detail->{$field} !== null) {
                return $detail->{$field};
            }
        }

        return 0;
    };
    $roman = ['I', 'II', 'III', 'IV', 'V'];
@endphp

<table class="no-border">
    <tr>
        <td class="center">
            <div>BỘ GIÁO DỤC VÀ ĐÀO TẠO</div>
            <div class="bold">CAO ĐẲNG CNTT TP.HCM</div>
        </td>
        <td class="center">
            <div class="bold">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</div>
            <div>Độc lập - Tự do - Hạnh phúc</div>
        </td>
    </tr>
</table>

<h2 class="center">BẢNG ĐÁNH GIÁ KẾT QUẢ RÈN LUYỆN CỦA SINH VIÊN</h2>
<p>
    <strong>Họ và tên:</strong> {{ $phieu->sinhVien->ho_ten }}
    &nbsp;&nbsp; <strong>MSSV:</strong> {{ $phieu->sinhVien->ma_sinh_vien }}
    &nbsp;&nbsp; <strong>Lớp:</strong> {{ $phieu->sinhVien->lop->ten_lop }}
    &nbsp;&nbsp; <strong>Khoa:</strong> {{ $phieu->sinhVien->lop->khoa?->ten_khoa }}
    &nbsp;&nbsp; <strong>Học kỳ:</strong> {{ $phieu->hocKy->ten_hoc_ky }}
    @if ($phieu->hocKy->namHoc)
        &nbsp;&nbsp; <strong>Năm học:</strong> {{ $phieu->hocKy->namHoc->ten_nam_hoc }}
    @endif
</p>

<table>
    <thead>
        <tr>
            <th rowspan="2" style="width: 36px">TT</th>
            <th rowspan="2">Nội dung đánh giá</th>
            <th rowspan="2" style="width: 52px">Điểm tối đa</th>
            <th colspan="2">Cá nhân đánh giá</th>
            <th colspan="2">GVCN/GVCV đánh giá</th>
            <th colspan="2">CTSV/Hội đồng</th>
        </tr>
        <tr>
            <th>Nhận xét hoặc minh chứng</th>
            <th style="width: 42px">Điểm</th>
            <th>Nhận xét hoặc minh chứng</th>
            <th style="width: 42px">Điểm</th>
            <th>Nhận xét</th>
            <th style="width: 42px">Điểm</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rubric as $section)
            @php
                $criterion = $section['criterion'];
                $indexLabel = $roman[$loop->index] ?? $loop->iteration;
            @endphp
            <tr class="section">
                <td class="center">{{ $indexLabel }}</td>
                <td>{{ $criterion->mo_ta ?? $criterion->ten_tieu_chi }}</td>
                <td class="center">{{ $criterion->diem_toi_da }}</td>
                <td colspan="6"></td>
            </tr>
            @foreach ($section['rows'] as $row)
                @php
                    $item = $row['item'];
                    $detail = $row['detail'];
                    $isHeading = $item->loai === \App\Models\MucTieuChi::TYPE_HEADING;
                @endphp
                @if ($isHeading)
                    <tr class="heading">
                        <td></td>
                        <td>{{ $item->ten_muc }}</td>
                        <td colspan="7"></td>
                    </tr>
                @else
                    <tr>
                        <td class="small">{{ $item->ma_muc }}</td>
                        <td>{{ $item->ten_muc }}</td>
                        <td class="center">{{ $item->diem_toi_da }}</td>
                        <td>
                            {{ $detail?->ghi_chu_sinh_vien }}
                            @if (($row['evidence'] ?? collect())->isNotEmpty())
                                <div class="small">
                                    Minh chứng:
                                    {{ ($row['evidence'] ?? collect())->pluck('ten_file')->join(', ') }}
                                </div>
                            @endif
                        </td>
                        <td class="center">{{ $scoreValue($detail, ['diem_tu_cham']) }}</td>
                        <td>{{ $detail?->ghi_chu_gvcn }}</td>
                        <td class="center">{{ $scoreValue($detail, ['diem_gvcn', 'diem_tu_cham']) }}</td>
                        <td>{{ $detail?->ghi_chu_hoi_dong }}</td>
                        <td class="center">{{ $scoreValue($detail, ['diem_hoi_dong', 'diem_gvcn', 'diem_tu_cham']) }}</td>
                    </tr>
                @endif
            @endforeach
            <tr class="total">
                <td></td>
                <td>Cộng mục ({{ $indexLabel }}):</td>
                <td class="center">{{ $criterion->diem_toi_da }}</td>
                <td></td>
                <td class="center">{{ $section['totals']['student'] }}</td>
                <td></td>
                <td class="center">{{ $section['totals']['gvcn'] }}</td>
                <td></td>
                <td class="center">{{ $section['totals']['hoi_dong'] }}</td>
            </tr>
        @endforeach
        <tr class="total">
            <td></td>
            <td>ĐIỂM TỔNG CỘNG (tối đa không quá 100 điểm):</td>
            <td></td>
            <td></td>
            <td class="center">{{ $phieu->diem_tu_cham }}</td>
            <td></td>
            <td class="center">{{ $phieu->diem_gvcn ?? $phieu->diem_tu_cham }}</td>
            <td></td>
            <td class="center">{{ $phieu->diem_hoi_dong ?? $phieu->diem_gvcn ?? $phieu->diem_tu_cham }}</td>
        </tr>
    </tbody>
</table>

<p><strong>Điểm tổng hợp:</strong> {{ $phieu->diem_cuoi ?? $phieu->diem_hoi_dong ?? $phieu->diem_gvcn ?? $phieu->diem_tu_cham }}/100</p>
<p><strong>Xếp loại:</strong> {{ $phieu->xep_loai ?? 'Chưa có' }}</p>

<table class="signatures">
    <tr>
        <td>CỐ VẤN HỌC TẬP<br><span class="small">(ký và ghi rõ họ tên)</span></td>
        <td>BAN CÁN SỰ LỚP<br><span class="small">(ký và ghi rõ họ tên)</span></td>
        <td>SINH VIÊN TỰ ĐÁNH GIÁ<br><span class="small">(ký và ghi rõ họ tên)</span></td>
        <td>PHÒNG CÔNG TÁC SINH VIÊN</td>
    </tr>
</table>
</body>
</html>
