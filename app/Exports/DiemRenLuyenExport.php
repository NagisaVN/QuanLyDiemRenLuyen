<?php

namespace App\Exports;

use App\Models\DiemRenLuyen;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DiemRenLuyenExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return DiemRenLuyen::with(['sinhVien.lop.khoa', 'hocKy.namHoc'])->latest()->get();
    }

    public function headings(): array
    {
        return ['Mã SV', 'Họ tên', 'Lớp', 'Khoa', 'Học kỳ', 'Năm học', 'Điểm', 'Điểm hoạt động', 'Xếp loại', 'Trạng thái'];
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
            $row->tong_diem,
            $row->diem_hoat_dong,
            $row->xep_loai,
            $row->trang_thai,
        ];
    }
}
