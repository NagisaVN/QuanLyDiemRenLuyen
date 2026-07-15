@php
    $user = auth()->user();
    $userName = $user?->name ?? 'Người dùng';
    $initial = mb_strtoupper(mb_substr($userName, 0, 1));
@endphp
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if ($user)
        <meta name="user-id" content="{{ $user->id }}">
    @endif
    <title>@yield('title', config('app.name'))</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@1.13.3/css/OverlayScrollbars.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <style>
        :root {
            --brand-blue: #2563eb;
            --brand-green: #059669;
            --brand-ink: #111827;
            --surface-line: #e5e7eb;
        }

        body {
            color: var(--brand-ink);
            letter-spacing: 0;
        }

        .main-sidebar {
            background: linear-gradient(180deg, #111827 0%, #1f2937 58%, #0f766e 100%);
        }

        .brand-link {
            border-bottom-color: rgba(255, 255, 255, .12) !important;
        }

        .brand-link .brand-text {
            font-weight: 700 !important;
            letter-spacing: 0;
        }

        .brand-mark,
        .sidebar-avatar {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 34px;
        }

        .brand-mark {
            background: rgba(37, 99, 235, .95);
            color: #fff;
            margin-left: .8rem;
            margin-right: .5rem;
            opacity: 1 !important;
        }

        .sidebar-avatar {
            background: rgba(255, 255, 255, .14);
            color: #fff;
            font-weight: 700;
        }

        .user-panel {
            border-bottom-color: rgba(255, 255, 255, .12) !important;
        }

        .user-panel .info a,
        .user-panel .info small {
            white-space: normal;
        }

        .nav-sidebar .nav-link {
            border-radius: 6px;
        }

        .nav-sidebar .nav-link p {
            white-space: normal;
        }

        .nav-sidebar > .nav-item > .nav-link.active {
            background: #2563eb;
            box-shadow: 0 8px 18px rgba(37, 99, 235, .25);
        }

        .content-wrapper {
            background: #f3f6fb;
        }

        .content-header h1 {
            font-size: 1.55rem;
            font-weight: 700;
        }

        .content-header .breadcrumb {
            font-size: .875rem;
        }

        .table-card {
            background: #fff;
            border: 0;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(17, 24, 39, .08), 0 10px 24px rgba(17, 24, 39, .04);
            overflow: hidden;
        }

        .stat-card {
            background: #fff;
            border: 0;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(17, 24, 39, .08), 0 10px 24px rgba(17, 24, 39, .04);
        }

        .table thead th {
            border-top: 0;
            font-size: .78rem;
            text-transform: uppercase;
            color: #6b7280;
            letter-spacing: .02em;
        }

        .btn {
            border-radius: 6px;
        }

        .small-box {
            border-radius: 6px;
            box-shadow: 0 8px 24px rgba(17, 24, 39, .08);
        }

        .small-box .icon {
            top: 12px;
        }

        .info-box {
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(17, 24, 39, .08), 0 10px 24px rgba(17, 24, 39, .04);
        }

        .form-select {
            display: block;
            width: 100%;
            height: calc(2.25rem + 2px);
            padding: .375rem 1.75rem .375rem .75rem;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5;
            color: #495057;
            vertical-align: middle;
            background: #fff;
            border: 1px solid #ced4da;
            border-radius: .25rem;
        }

        select.form-select[multiple],
        select.form-select[size] {
            height: auto;
            padding-right: .75rem;
        }

        .text-bg-primary,
        .text-bg-info,
        .text-bg-secondary,
        .text-bg-success,
        .text-bg-warning,
        .text-bg-danger,
        .text-bg-dark {
            color: #fff !important;
        }

        .text-bg-primary { background-color: #007bff !important; }
        .text-bg-info { background-color: #17a2b8 !important; }
        .text-bg-secondary { background-color: #6c757d !important; }
        .text-bg-success { background-color: #28a745 !important; }
        .text-bg-warning { background-color: #ffc107 !important; color: #1f2937 !important; }
        .text-bg-danger { background-color: #dc3545 !important; }
        .text-bg-dark { background-color: #343a40 !important; }

        .fw-semibold { font-weight: 600 !important; }
        .fw-bold { font-weight: 700 !important; }
        .me-1 { margin-right: .25rem !important; }
        .me-2 { margin-right: .5rem !important; }
        .me-3 { margin-right: 1rem !important; }
        .ms-1 { margin-left: .25rem !important; }
        .ms-2 { margin-left: .5rem !important; }
        .ms-3 { margin-left: 1rem !important; }
        .gap-1 { gap: .25rem !important; }
        .gap-2 { gap: .5rem !important; }
        .gap-3 { gap: 1rem !important; }
        .gap-4 { gap: 1.5rem !important; }
        .rounded-2 { border-radius: .25rem !important; }
        .rounded-3 { border-radius: .3rem !important; }
        .vstack {
            display: flex;
            flex: 1 1 auto;
            flex-direction: column;
            align-self: stretch;
        }

        .row.g-2,
        .row.g-3,
        .row.g-4 {
            margin-bottom: -1rem;
        }

        .row.g-2 > [class*="col-"],
        .row.g-3 > [class*="col-"],
        .row.g-4 > [class*="col-"] {
            margin-bottom: 1rem;
        }

        @media (max-width: 575.98px) {
            .content-header h1 {
                font-size: 1.25rem;
            }

            .content-header .breadcrumb {
                display: none;
            }

            .table-card.p-3,
            .table-card.p-4 {
                padding: 1rem !important;
            }
        }
    </style>
    @stack('styles')
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed">
<div class="wrapper">
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button" aria-label="Thu gọn menu">
                    <i class="fas fa-bars"></i>
                </a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="{{ route('dashboard') }}" class="nav-link">Bảng điều khiển</a>
            </li>
        </ul>

        <ul class="navbar-nav ml-auto">
            @can('view student notifications')
                <li class="nav-item dropdown">
                    <a class="nav-link" data-toggle="dropdown" href="#" aria-label="Thông báo" aria-expanded="false">
                        <i class="far fa-bell"></i>
                        <span id="student-notification-count" class="badge badge-danger navbar-badge {{ $unreadCount ? '' : 'd-none' }}">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right" style="min-width:340px;max-width:380px">
                        <span class="dropdown-item dropdown-header"><strong>Thông báo</strong> · {{ $unreadCount }} chưa đọc</span>
                        <div class="dropdown-divider"></div>
                        @forelse ($notifications as $notification)
                            <form method="POST" action="{{ route('sinh-vien.notifications.read', $notification) }}">
                                @csrf
                                @method('PATCH')
                                <button class="dropdown-item text-left py-2 {{ $notification->is_read ? '' : 'bg-light' }}" type="submit" style="white-space:normal">
                                    <div class="d-flex align-items-start">
                                        <i class="{{ $notification->isEvaluation() ? 'fas fa-clipboard-check text-primary' : 'fas fa-bullhorn text-success' }} mr-2 mt-1"></i>
                                        <span class="flex-grow-1">
                                            <span class="d-block font-weight-bold">{{ $notification->title }}</span>
                                            <span class="d-block small text-muted">{{ \Illuminate\Support\Str::limit($notification->content, 90) }}</span>
                                            <span class="d-block small text-muted mt-1">{{ $notification->created_at->diffForHumans() }}</span>
                                        </span>
                                        @if (! $notification->is_read)
                                            <span class="badge badge-danger ml-1">Mới</span>
                                        @endif
                                    </div>
                                </button>
                            </form>
                            <div class="dropdown-divider"></div>
                        @empty
                            <div class="dropdown-item text-center text-muted py-3">Chưa có thông báo</div>
                            <div class="dropdown-divider"></div>
                        @endforelse
                        <a href="{{ route('sinh-vien.notifications.index') }}" class="dropdown-item dropdown-footer">Xem tất cả thông báo</a>
                    </div>
                </li>
            @endcan
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#" aria-expanded="false">
                    <i class="far fa-user-circle mr-1"></i>
                    <span class="d-none d-md-inline">{{ $userName }}</span>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <span class="dropdown-item dropdown-header">{{ $userName }}</span>
                    <div class="dropdown-divider"></div>
                    <a href="{{ route('profile.edit') }}" class="dropdown-item">
                        <i class="fas fa-user-cog mr-2"></i> Hồ sơ
                    </a>
                    <div class="dropdown-divider"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="dropdown-item text-danger" type="submit">
                            <i class="fas fa-sign-out-alt mr-2"></i> Đăng xuất
                        </button>
                    </form>
                </div>
            </li>
        </ul>
    </nav>

    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="{{ route('dashboard') }}" class="brand-link">
            <span class="brand-mark"><i class="fas fa-graduation-cap"></i></span>
            <span class="brand-text">Điểm rèn luyện</span>
        </a>

        <div class="sidebar">
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image pl-1">
                    <span class="sidebar-avatar">{{ $initial }}</span>
                </div>
                <div class="info">
                    <a href="{{ route('profile.edit') }}" class="d-block">{{ $userName }}</a>
                    <small class="text-muted">@yield('layout-title')</small>
                </div>
            </div>

            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent" data-widget="treeview" role="menu" data-accordion="false">
                    @yield('nav')
                </ul>
            </nav>
        </div>
    </aside>

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2 align-items-center">
                    <div class="col-sm-8">
                        <h1>@yield('page-title', 'Bảng điều khiển')</h1>
                        <div class="text-muted small">@yield('layout-title') · {{ $userName }}</div>
                    </div>
                    <div class="col-sm-4">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Trang chủ</a></li>
                            <li class="breadcrumb-item active">@yield('page-title', 'Bảng điều khiển')</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid pb-4">
                @if (session('status'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle mr-2"></i>{{ config('ui.messages.' . session('status'), session('status')) }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Đóng">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger" role="alert">
                        <div class="fw-semibold"><i class="fas fa-exclamation-triangle mr-2"></i>Vui lòng kiểm tra lại dữ liệu.</div>
                        <ul class="mb-0 mt-2">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @yield('content')
            </div>
        </section>
    </div>

    <footer class="main-footer small">
        <strong>{{ config('app.name') }}</strong>
        <span class="ml-1">Quản lý điểm rèn luyện sinh viên</span>
    </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@1.13.3/js/jquery.overlayScrollbars.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
@if (file_exists(public_path('hot')) || file_exists(public_path('build/manifest.json')))
    @vite('resources/js/app.js')
@endif
@stack('scripts')
</body>
</html>
