<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hệ thống Quản lý Điểm rèn luyện - {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1D2865; /* ITC dark blue */
            --secondary-color: #EC1E28; /* ITC red */
            --accent-color: #0366ff;
            --text-color: #333333;
            --bg-light: #f8f9fc;
        }
        body { 
            font-family: 'Inter', sans-serif;
            color: var(--text-color);
            background: #ffffff; 
        }
        
        /* Navbar */
        .navbar {
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            padding: 15px 0;
            transition: all 0.3s ease;
        }
        .navbar-brand {
            font-weight: 800;
            color: var(--primary-color) !important;
            font-size: 1.5rem;
            letter-spacing: -0.5px;
        }
        .nav-link {
            font-weight: 600;
            color: var(--primary-color);
            transition: color 0.3s;
            text-transform: uppercase;
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }
        .nav-link:hover, .nav-link.active {
            color: var(--secondary-color);
        }
        .btn-login {
            background-color: var(--primary-color);
            color: white;
            border-radius: 8px;
            padding: 10px 24px;
            font-weight: 600;
            transition: all 0.3s;
            border: 2px solid var(--primary-color);
        }
        .btn-login:hover {
            background-color: transparent;
            color: var(--primary-color);
        }

        /* Hero Section */
        .hero-section {
            padding: 160px 0 100px;
            background: linear-gradient(135deg, var(--bg-light) 0%, #e0e7ff 100%);
            position: relative;
            overflow: hidden;
        }
        .hero-section::before {
            content: '';
            position: absolute;
            top: -150px;
            right: -100px;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(3,102,255,0.08) 0%, rgba(255,255,255,0) 70%);
        }
        .hero-title {
            color: var(--primary-color);
            font-weight: 800;
            font-size: 3.5rem;
            line-height: 1.2;
            margin-bottom: 20px;
            letter-spacing: -1px;
        }
        .hero-subtitle {
            font-size: 1.15rem;
            color: #555;
            margin-bottom: 40px;
            line-height: 1.7;
        }
        .hero-btn {
            background-color: var(--secondary-color);
            color: white;
            border: 2px solid var(--secondary-color);
            padding: 14px 36px;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            box-shadow: 0 8px 20px rgba(236, 30, 40, 0.25);
            transition: all 0.3s;
        }
        .hero-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(236, 30, 40, 0.35);
            color: white;
            background-color: #c91922;
            border-color: #c91922;
        }
        .hero-btn-outline {
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            padding: 14px 36px;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s;
            background: transparent;
        }
        .hero-btn-outline:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(29, 40, 101, 0.2);
        }

        /* Stats Section */
        .stats-section {
            padding: 70px 0;
            background: linear-gradient(to right, var(--primary-color), #2d3b8e);
            color: white;
        }
        .stat-item {
            text-align: center;
            position: relative;
        }
        .stat-item:not(:last-child)::after {
            content: '';
            position: absolute;
            right: 0;
            top: 20%;
            height: 60%;
            width: 1px;
            background: rgba(255,255,255,0.2);
        }
        .stat-number {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 5px;
            color: #fff;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .stat-label {
            font-size: 1.1rem;
            font-weight: 500;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Features Section */
        .features-section {
            padding: 100px 0;
            background-color: #ffffff;
        }
        .section-title {
            text-align: center;
            color: var(--primary-color);
            font-weight: 800;
            margin-bottom: 60px;
            font-size: 2.5rem;
            position: relative;
            text-transform: uppercase;
        }
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -20px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background-color: var(--secondary-color);
            border-radius: 2px;
        }
        .feature-card {
            border: none;
            border-radius: 16px;
            padding: 40px 30px;
            text-align: center;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            background: #fff;
            box-shadow: 0 10px 40px rgba(0,0,0,0.05);
            height: 100%;
            position: relative;
            z-index: 1;
            overflow: hidden;
        }
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(135deg, var(--bg-light) 0%, #fff 100%);
            z-index: -1;
            transition: opacity 0.3s;
            opacity: 0;
        }
        .feature-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 20px 50px rgba(29, 40, 101, 0.1);
        }
        .feature-card:hover::before {
            opacity: 1;
        }
        .feature-icon {
            width: 80px;
            height: 80px;
            background: rgba(236, 30, 40, 0.1);
            color: var(--secondary-color);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            margin: 0 auto 25px;
            transition: all 0.3s;
            transform: rotate(-5deg);
        }
        .feature-card:hover .feature-icon {
            background: var(--secondary-color);
            color: white;
            transform: rotate(0);
        }
        .feature-title {
            font-weight: 700;
            margin-bottom: 15px;
            color: var(--primary-color);
            font-size: 1.25rem;
        }
        .feature-desc {
            color: #666;
            font-size: 1rem;
            line-height: 1.6;
        }

        /* Footer */
        .footer {
            background-color: var(--primary-color);
            color: rgba(255,255,255,0.7);
            padding: 60px 0 20px;
            border-top: 5px solid var(--secondary-color);
        }
        .footer-title {
            color: white;
            font-weight: 700;
            margin-bottom: 25px;
            text-transform: uppercase;
            font-size: 1.1rem;
            letter-spacing: 1px;
        }
        .footer a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: color 0.3s;
        }
        .footer a:hover {
            color: white;
            padding-left: 5px;
        }
        .footer ul li {
            transition: all 0.3s;
        }
        
        .floating-img {
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0px); }
        }

        @media (max-width: 768px) {
            .hero-title { font-size: 2.5rem; }
            .stat-item:not(:last-child)::after { display: none; }
            .stat-item { margin-bottom: 30px; }
            .hero-section { padding: 120px 0 60px; text-align: center; }
            .hero-btn, .hero-btn-outline { width: 100%; margin-bottom: 10px; }
            .d-flex.gap-3.flex-wrap { flex-direction: column; }
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <i class="bi bi-mortarboard-fill text-danger me-2 fs-2"></i>
                <span style="color: var(--secondary-color);">ITC</span><span class="ms-1">DRL</span>
            </a>
            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="bi bi-list fs-1 text-primary"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Trang chủ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Chức năng</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Giới thiệu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Liên hệ</a>
                    </li>
                </ul>
                <div class="d-flex mt-3 mt-lg-0 align-items-center justify-content-center">
                    <a href="{{ route('login') }}" class="btn btn-login"><i class="bi bi-box-arrow-in-right me-2"></i>ĐĂNG NHẬP</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 z-1">
                    <span class="badge mb-3 px-3 py-2 rounded-pill shadow-sm" style="background-color: rgba(236, 30, 40, 0.1); color: var(--secondary-color); font-weight: 600; font-size: 0.9rem;">
                        <i class="bi bi-stars me-1"></i> Giải pháp công nghệ hiện đại
                    </span>
                    <h1 class="hero-title">Hệ Thống Quản Lý<br><span style="color: var(--secondary-color);">Điểm Rèn Luyện</span> Sinh Viên</h1>
                    <p class="hero-subtitle">Nền tảng chuyển đổi số toàn diện hỗ trợ sinh viên tự đánh giá, nộp minh chứng trực tuyến và giúp nhà trường quản lý điểm rèn luyện minh bạch, nhanh chóng, chính xác.</p>
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="{{ route('login') }}" class="btn hero-btn"><i class="bi bi-rocket-takeoff me-2"></i>Truy Cập Hệ Thống</a>
                        <a href="#features" class="btn hero-btn-outline">Khám Phá Tính Năng</a>
                    </div>
                </div>
                <div class="col-lg-6 text-center mt-5 mt-lg-0 d-none d-lg-block position-relative">
                    <div class="position-absolute top-50 start-50 translate-middle w-100 h-100 rounded-circle" style="background: radial-gradient(circle, rgba(29, 40, 101, 0.1) 0%, transparent 70%); z-index: 0;"></div>
                    <img src="https://ui-avatars.com/api/?name=ITC&background=1D2865&color=fff&size=400&font-size=0.33&rounded=true" class="img-fluid floating-img shadow-lg rounded-circle border border-5 border-white position-relative" alt="Hero Image" style="max-width: 420px; width: 100%; z-index: 1;">
                    <!-- Floating badges -->
                    <div class="position-absolute bg-white px-4 py-3 rounded-4 shadow-lg floating-img" style="bottom: 10%; left: 0; animation-delay: 1s; z-index: 2;">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-success rounded-circle p-2 text-white"><i class="bi bi-check-lg fs-4"></i></div>
                            <div class="text-start">
                                <div class="fw-bold text-dark fs-5">100%</div>
                                <div class="text-muted small">Số hóa quy trình</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row g-4 justify-content-center">
                <div class="col-6 col-md-3 stat-item">
                    <div class="stat-number">100</div>
                    <div class="stat-label">Thang Điểm Chuẩn</div>
                </div>
                <div class="col-6 col-md-3 stat-item">
                    <div class="stat-number">5MB</div>
                    <div class="stat-label">Max Minh Chứng</div>
                </div>
                <div class="col-6 col-md-3 stat-item">
                    <div class="stat-number">5</div>
                    <div class="stat-label">Cấp Phân Quyền</div>
                </div>
                <div class="col-6 col-md-3 stat-item">
                    <div class="stat-number">3</div>
                    <div class="stat-label">Học Kỳ / Năm</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features-section">
        <div class="container">
            <h2 class="section-title">Chức Năng Nổi Bật</h2>
            <div class="row g-4 mt-3">
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-person-lines-fill"></i>
                        </div>
                        <h4 class="feature-title">Tự Đánh Giá Trực Tuyến</h4>
                        <p class="feature-desc">Sinh viên chủ động chấm điểm rèn luyện theo các tiêu chí và học kỳ quy định, tải lên minh chứng trực tiếp một cách dễ dàng.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-qr-code-scan"></i>
                        </div>
                        <h4 class="feature-title">Điểm Danh QR Code</h4>
                        <p class="feature-desc">Tự động hóa việc điểm danh tham gia các hoạt động ngoại khóa, hội thảo bằng mã QR, cập nhật điểm tức thời.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-check2-all"></i>
                        </div>
                        <h4 class="feature-title">Duyệt Điểm Đa Cấp</h4>
                        <p class="feature-desc">Quy trình duyệt điểm chặt chẽ qua nhiều cấp: Lớp trưởng, Giáo viên chủ nhiệm, Khoa và Hội đồng trường.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-file-earmark-bar-graph"></i>
                        </div>
                        <h4 class="feature-title">Báo Cáo & Thống Kê</h4>
                        <p class="feature-desc">Trích xuất dữ liệu báo cáo chuyên nghiệp (PDF, Excel), cung cấp biểu đồ trực quan về tình hình rèn luyện.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <h4 class="feature-title">Phân Quyền Chặt Chẽ</h4>
                        <p class="feature-desc">Cơ chế bảo mật với 5 vai trò riêng biệt, đảm bảo quyền hạn và trách nhiệm rõ ràng của từng cá nhân.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-cloud-check"></i>
                        </div>
                        <h4 class="feature-title">Lưu Trữ An Toàn</h4>
                        <p class="feature-desc">Hệ thống tự động sao lưu dữ liệu, đảm bảo hồ sơ điểm rèn luyện được lưu trữ lâu dài, an toàn và toàn vẹn.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-5">
                    <h5 class="footer-title d-flex align-items-center">
                        <i class="bi bi-mortarboard-fill fs-3 me-2" style="color: var(--secondary-color);"></i>
                        HỆ THỐNG DRL ITC
                    </h5>
                    <p class="mb-3 pe-lg-4" style="line-height: 1.8;">Nền tảng công nghệ số hóa quy trình đánh giá điểm rèn luyện, mang lại sự tiện lợi, minh bạch và hiệu quả cho sinh viên và Nhà trường.</p>
                    <div class="d-flex gap-3 fs-5">
                        <a href="#"><i class="bi bi-facebook"></i></a>
                        <a href="#"><i class="bi bi-youtube"></i></a>
                        <a href="#"><i class="bi bi-globe"></i></a>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h5 class="footer-title">Liên Kết Nhanh</h5>
                    <ul class="list-unstyled">
                        <li class="mb-3"><a href="#"><i class="bi bi-chevron-right me-2 text-danger"></i>Trang chủ</a></li>
                        <li class="mb-3"><a href="#features"><i class="bi bi-chevron-right me-2 text-danger"></i>Chức năng hệ thống</a></li>
                        <li class="mb-3"><a href="#"><i class="bi bi-chevron-right me-2 text-danger"></i>Hướng dẫn sử dụng</a></li>
                        <li class="mb-3"><a href="{{ route('login') }}"><i class="bi bi-chevron-right me-2 text-danger"></i>Đăng nhập</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 col-md-6">
                    <h5 class="footer-title">Thông Tin Hỗ Trợ</h5>
                    <ul class="list-unstyled">
                        <li class="mb-3 d-flex align-items-start">
                            <i class="bi bi-telephone fs-5 me-3" style="color: var(--secondary-color);"></i>
                            <div>
                                <strong>Hotline / Zalo:</strong><br>
                                093 886 1080
                            </div>
                        </li>
                        <li class="mb-3 d-flex align-items-start">
                            <i class="bi bi-envelope fs-5 me-3" style="color: var(--secondary-color);"></i>
                            <div>
                                <strong>Email:</strong><br>
                                hotro@itc.edu.vn
                            </div>
                        </li>
                        <li class="mb-3 d-flex align-items-start">
                            <i class="bi bi-geo-alt fs-5 me-3" style="color: var(--secondary-color);"></i>
                            <div>
                                <strong>Địa chỉ:</strong><br>
                                12 Trịnh Đình Thảo, P. Hòa Thạnh, Q. Tân Phú, TP. HCM
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
            <hr class="mt-5 mb-4" style="border-color: rgba(255,255,255,0.1);">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                    <small>&copy; {{ date('Y') }} Bản quyền thuộc về Trường Cao đẳng Công nghệ Thông tin TP.HCM (ITC).</small>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <small>Phát triển trên nền tảng Laravel & Bootstrap 5</small>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            var navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.style.boxShadow = '0 5px 20px rgba(0,0,0,0.1)';
                navbar.style.padding = '10px 0';
            } else {
                navbar.style.boxShadow = '0 2px 15px rgba(0,0,0,0.05)';
                navbar.style.padding = '15px 0';
            }
        });
    </script>
</body>
</html>
