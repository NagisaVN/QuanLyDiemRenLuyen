@php
    $user = auth()->user();

    $submittedFormsUrl = null;
    $pendingFormsUrl = null;
    $openActivitiesUrl = null;
    $studentCountUrl = null;

    if ($user?->hasRole('admin')) {
        $openActivitiesUrl = route('doan-hoi.activities.index');
        $studentCountUrl = route('admin.crud.index', 'sinh-viens');
    }

    if ($user?->hasRole('sinh_vien')) {
        $submittedFormsUrl = route('sinh-vien.evaluations.index');
        $openActivitiesUrl = route('sinh-vien.activities.index');
    }

    if ($user?->hasRole('gvcn')) {
        $submittedFormsUrl = route('gvcn.evaluations.index');
        $pendingFormsUrl = route('gvcn.evaluations.index');
    }

    if ($user?->hasRole('can_bo_doan_hoi')) {
        $openActivitiesUrl = route('doan-hoi.activities.index');
    }

    if ($user?->hasRole('hoi_dong_khoa')) {
        $submittedFormsUrl = route('hoi-dong.evaluations.index');
    }
@endphp

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $submittedForms }}</h3>
                <p>Sinh viên đã nộp phiếu</p>
            </div>
            <div class="icon">
                <i class="fas fa-file-signature"></i>
            </div>
            @if ($submittedFormsUrl)
                <a href="{{ $submittedFormsUrl }}" class="small-box-footer">
                    Xem chi tiết <i class="fas fa-arrow-circle-right"></i>
                </a>
            @endif
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $pendingForms }}</h3>
                <p>Phiếu chờ duyệt</p>
            </div>
            <div class="icon">
                <i class="fas fa-hourglass-half"></i>
            </div>
            @if ($pendingFormsUrl)
                <a href="{{ $pendingFormsUrl }}" class="small-box-footer">
                    Xem chi tiết <i class="fas fa-arrow-circle-right"></i>
                </a>
            @endif
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $openActivities }}</h3>
                <p>Hoạt động đang mở</p>
            </div>
            <div class="icon">
                <i class="fas fa-calendar-check"></i>
            </div>
            @if ($openActivitiesUrl)
                <a href="{{ $openActivitiesUrl }}" class="small-box-footer">
                    Xem chi tiết <i class="fas fa-arrow-circle-right"></i>
                </a>
            @endif
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ $studentCount }}</h3>
                <p>Tổng sinh viên</p>
            </div>
            <div class="icon">
                <i class="fas fa-user-graduate"></i>
            </div>
            @if ($studentCountUrl)
                <a href="{{ $studentCountUrl }}" class="small-box-footer">
                    Xem chi tiết <i class="fas fa-arrow-circle-right"></i>
                </a>
            @endif
        </div>
    </div>
</div>

<div class="card card-primary table-card mb-4">
    <div class="card-header">
        <h3 class="card-title mb-0">Tỷ lệ xếp loại điểm rèn luyện</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Thu gọn">
                <i class="fas fa-minus"></i>
            </button>
            <button type="button" class="btn btn-tool" data-card-widget="remove" aria-label="Đóng">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="chart">
            <canvas id="rankChart" style="min-height: 300px; height: 300px; max-height: 300px; max-width: 100%;"></canvas>
        </div>
    </div>
</div>

@push('scripts')
<script>
const rankLabels = @json(array_keys($rankGroups));
const rankData = @json(array_values($rankGroups));
const hasRankData = rankLabels.length > 0 && rankData.length > 0;
new Chart(document.getElementById('rankChart'), {
    type: 'doughnut',
    data: {
        labels: hasRankData ? rankLabels : ['Chưa có dữ liệu'],
        datasets: [{
            data: hasRankData ? rankData : [1],
            backgroundColor: hasRankData
                ? ['#2563eb', '#059669', '#f59e0b', '#0ea5e9', '#7c3aed', '#64748b']
                : ['#cbd5e1'],
            borderColor: '#ffffff',
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '52%',
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    boxWidth: 42,
                    padding: 16,
                    font: {
                        size: 13
                    }
                }
            }
        }
    }
});
</script>
@endpush
