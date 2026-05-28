<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f5f7fb; }
        .hero { min-height: 100vh; display: flex; align-items: center; }
    </style>
</head>
<body>
    <main class="hero">
        <div class="container py-5">
            <div class="row align-items-center g-5">
                <div class="col-lg-7">
                    <span class="badge text-bg-primary mb-3">Nền tảng Laravel, MySQL và Bootstrap 5</span>
                    <h1 class="display-5 fw-bold">Quản lý điểm rèn luyện sinh viên</h1>
                    <p class="lead text-secondary">Tự đánh giá, tải lên minh chứng, duyệt cấp GVCN, xác nhận hội đồng, điểm danh QR và báo cáo điểm theo học kỳ.</p>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('login') }}" class="btn btn-primary btn-lg"><i class="bi bi-box-arrow-in-right me-2"></i>Đăng nhập</a>
                        <a href="#features" class="btn btn-outline-secondary btn-lg">Xem chức năng</a>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-body p-4">
                            <div class="row g-3">
                                @foreach ([['100', 'Thang điểm'], ['5MB', 'Mỗi file'], ['5', 'Minh chứng/phiếu'], ['3', 'Học kỳ/năm']] as [$value, $label])
                                    <div class="col-6">
                                        <div class="p-3 bg-light rounded-3">
                                            <div class="h3 mb-0 text-primary">{{ $value }}</div>
                                            <div class="text-secondary">{{ $label }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="features" class="row g-3 mt-5">
                @foreach (['Phân quyền 5 vai trò', 'Tự đánh giá theo học kỳ', 'Duyệt minh chứng', 'Điểm danh QR hoạt động', 'Xuất Excel/PDF', 'Sao lưu hằng ngày'] as $feature)
                    <div class="col-md-4">
                        <div class="bg-white border rounded-3 p-3 h-100"><i class="bi bi-check-circle text-success me-2"></i>{{ $feature }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </main>
</body>
</html>
