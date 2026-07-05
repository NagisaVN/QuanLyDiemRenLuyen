@extends('layouts.hoi-dong')

@section('page-title', 'Xuất báo cáo điểm rèn luyện')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="table-card p-4">
            <h5 class="mb-4">Tùy chọn xuất báo cáo</h5>
            
            <form id="export-form" method="GET" action="{{ route('hoi-dong.export.excel') }}">
                <div class="mb-3">
                    <label class="form-label fw-medium">Chọn học kỳ / Năm học</label>
                    <select class="form-select" name="hoc_ky_id" required>
                        <option value="">-- Chọn học kỳ --</option>
                        @foreach($hocKys as $hk)
                            <option value="{{ $hk->id }}">{{ $hk->ten_hoc_ky }} (Năm học {{ $hk->namHoc->ten_nam_hoc ?? 'N/A' }})</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="form-label fw-medium d-block">Định dạng xuất</label>
                    
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="format" id="formatExcel" value="excel" checked onchange="updateFormAction(this.value)">
                            <label class="form-check-label" for="formatExcel">
                                <i class="fas fa-file-excel text-success me-1"></i> Excel (.xlsx)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="format" id="formatPdf" value="pdf" onchange="updateFormAction(this.value)">
                            <label class="form-check-label" for="formatPdf">
                                <i class="fas fa-file-pdf text-danger me-1"></i> PDF (.pdf)
                            </label>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-download me-2"></i> Tải xuống báo cáo
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function updateFormAction(format) {
        const form = document.getElementById('export-form');
        if (format === 'excel') {
            form.action = "{{ route('hoi-dong.export.excel') }}";
        } else {
            form.action = "{{ route('hoi-dong.export.pdf') }}";
        }
    }
</script>
@endpush
