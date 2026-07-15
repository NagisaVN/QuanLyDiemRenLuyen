<?php

namespace App\Exports;

use App\Models\DiemRenLuyen;
use App\Models\DotDanhGia;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DiemRenLuyenExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(
        private readonly ?DotDanhGia $dotDanhGia = null,
        private readonly ?int $hocKyId = null
    ) {}

    public function query()
    {
        return DiemRenLuyen::query()
            ->with(['sinhVien.lop.khoa', 'hocKy.namHoc', 'phieuDanhGia.dotDanhGia'])
            ->when($this->dotDanhGia, fn ($query) => $query->whereHas(
                'phieuDanhGia',
                fn ($formQuery) => $formQuery->where('dot_danh_gia_id', $this->dotDanhGia->id)
            ))
            ->when($this->hocKyId, fn ($query) => $query->where('hoc_ky_id', $this->hocKyId))
            ->latest();
    }

    public function headings(): array
    {
        return ['Mã SV', 'Họ tên', 'Lớp', 'Khoa', 'Học kỳ', 'Năm học', 'Đợt đánh giá', 'Điểm', 'Điểm hoạt động', 'Xếp loại', 'Trạng thái'];
    }

    public function map($row): array
    {
        return [
            $row->sinhVien?->ma_sinh_vien,
            $row->sinhVien?->ho_ten,
            $row->sinhVien?->lop?->ten_lop,
            $row->sinhVien?->lop?->khoa?->ten_khoa,
            $row->hocKy?->ten_hoc_ky,
            $row->hocKy?->namHoc?->ten_nam_hoc,
            $row->phieuDanhGia?->dotDanhGia?->ten_dot,
            $row->tong_diem,
            $row->diem_hoat_dong,
            $row->xep_loai,
            $row->trang_thai,
        ];
    }
}
