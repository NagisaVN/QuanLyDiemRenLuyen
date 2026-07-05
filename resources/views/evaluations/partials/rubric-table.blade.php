@php
    $stage = $stage ?? 'student';
    $canEdit = $canEdit ?? false;
    $showHoiDong = $showHoiDong ?? true;
    $roman = ['I', 'II', 'III', 'IV', 'V'];
    $scoreValue = function ($detail, array $fields): mixed {
        foreach ($fields as $field) {
            if ($detail && $detail->{$field} !== null) {
                return $detail->{$field};
            }
        }

        return 0;
    };
    $scoreInput = function ($item, $detail, string $field, array $fallback = []) use ($stage, $canEdit, $scoreValue): string {
        $name = "scores[{$item->id}]";
        $oldKey = "scores.{$item->id}";
        $value = old($oldKey, $scoreValue($detail, [$field, ...$fallback]));
        $limit = (int) $item->diem_toi_da;
        $min = min(0, $limit);
        $max = max(0, $limit);
        $disabled = $canEdit ? '' : ' disabled';

        return '<input class="form-control form-control-sm text-center" type="number" step="1" min="'.$min.'" max="'.$max.'" name="'.$name.'" value="'.e($value).'"'.$disabled.'>';
    };
    $noteInput = function ($item, $detail, string $field) use ($canEdit): string {
        $name = "notes[{$item->id}]";
        $oldKey = "notes.{$item->id}";
        $value = old($oldKey, $detail?->{$field});
        $disabled = $canEdit ? '' : ' disabled';

        return '<textarea class="form-control form-control-sm drl-note" name="'.$name.'" rows="2"'.$disabled.'>'.e($value).'</textarea>';
    };
@endphp

@once
    @push('styles')
        <style>
            .drl-table th,
            .drl-table td {
                vertical-align: middle;
            }

            .drl-table thead th {
                position: sticky;
                background-color: #f8f9fa;
                z-index: 2;
                box-shadow: inset 0 -1px 0 #dee2e6;
            }

            /* Fix sticky header with 2 rows */
            .drl-table thead tr:nth-child(1) th {
                top: 0;
            }
            .drl-table thead tr:nth-child(2) th {
                top: 40px; /* Approximate height of first row, adjust if needed */
            }

            .drl-table tbody tr {
                transition: all 0.2s ease;
            }
            .drl-table tbody tr:hover {
                background-color: rgba(0, 0, 0, 0.015);
            }

            .drl-table .form-control-sm {
                min-height: 38px;
                border-radius: 8px;
                transition: all 0.2s;
            }
            
            .drl-table .form-control-sm:focus {
                border-color: var(--bs-primary);
                box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb), 0.15);
            }

            /* Hide spin buttons */
            .drl-table input[type="number"]::-webkit-inner-spin-button,
            .drl-table input[type="number"]::-webkit-outer-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }
            .drl-table input[type="number"] {
                -moz-appearance: textfield;
            }

            .drl-note {
                min-width: 190px;
                resize: vertical;
                border-radius: 8px;
                background-color: #fdfdfd;
            }
            .drl-note:focus {
                background-color: #fff;
            }

            /* Custom Scrollbar for the table container */
            .drl-table-container::-webkit-scrollbar {
                height: 8px;
                width: 8px;
            }
            .drl-table-container::-webkit-scrollbar-track {
                background: #f8f9fa;
                border-radius: 4px;
            }
            .drl-table-container::-webkit-scrollbar-thumb {
                background: #cbd5e1;
                border-radius: 4px;
            }
            .drl-table-container::-webkit-scrollbar-thumb:hover {
                background: #94a3b8;
            }
            
            /* Cursor styles for dragging */
            .drl-table-container {
                cursor: grab;
            }
            .drl-table-container:active {
                cursor: grabbing;
            }
            .drl-table-container input,
            .drl-table-container textarea,
            .drl-table-container button,
            .drl-table-container a {
                cursor: text;
            }
            .drl-table-container button,
            .drl-table-container a {
                cursor: pointer;
            }
        </style>
    @endpush
@endonce

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const slider = document.querySelector('.drl-table-container');
                let isDown = false;
                let startX;
                let scrollLeft;

                if (!slider) return;

                slider.addEventListener('mousedown', (e) => {
                    // Prevent drag when clicking on form elements
                    if (['INPUT', 'TEXTAREA', 'BUTTON', 'A', 'SELECT'].includes(e.target.tagName)) return;
                    
                    isDown = true;
                    startX = e.pageX - slider.offsetLeft;
                    scrollLeft = slider.scrollLeft;
                });
                
                slider.addEventListener('mouseleave', () => {
                    isDown = false;
                });
                
                slider.addEventListener('mouseup', () => {
                    isDown = false;
                });
                
                slider.addEventListener('mousemove', (e) => {
                    if (!isDown) return;
                    e.preventDefault();
                    const x = e.pageX - slider.offsetLeft;
                    const walk = (x - startX) * 1.5; // Scroll speed multiplier
                    slider.scrollLeft = scrollLeft - walk;
                });
            });
        </script>
    @endpush
@endonce

<div class="table-responsive drl-table-container pb-2">
    <table class="table table-bordered align-middle drl-table mb-0">
        <thead class="table-light">
            <tr>
                <th rowspan="2" class="text-center text-secondary" style="width: 54px">TT</th>
                <th rowspan="2" class="text-secondary" style="min-width: 360px">Nội dung đánh giá</th>
                <th rowspan="2" class="text-center text-secondary" style="width: 88px">Điểm tối đa</th>
                <th colspan="2" class="text-center text-secondary">Cá nhân đánh giá</th>
                <th colspan="2" class="text-center text-secondary">GVCN/GVCV đánh giá</th>
                @if ($showHoiDong)
                    <th colspan="2" class="text-center text-secondary">CTSV/Hội đồng</th>
                @endif
            </tr>
            <tr>
                <th class="text-secondary" style="min-width: 220px">Nhận xét hoặc minh chứng</th>
                <th class="text-center text-secondary" style="width: 110px">Điểm</th>
                <th class="text-secondary" style="min-width: 220px">Nhận xét hoặc minh chứng</th>
                <th class="text-center text-secondary" style="width: 110px">Điểm</th>
                @if ($showHoiDong)
                    <th class="text-secondary" style="min-width: 220px">Nhận xét</th>
                    <th class="text-center text-secondary" style="width: 110px">Điểm</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach ($rubric as $section)
                @php
                    $criterion = $section['criterion'];
                    $indexLabel = $roman[$loop->index] ?? $loop->iteration;
                @endphp
                <tr class="table-primary opacity-75">
                    <th class="text-center">{{ $indexLabel }}</th>
                    <th>{{ $criterion->mo_ta ?? $criterion->ten_tieu_chi }}</th>
                    <th class="text-center">{{ $criterion->diem_toi_da }}</th>
                    <th colspan="{{ $showHoiDong ? 6 : 4 }}"></th>
                </tr>

                @foreach ($section['rows'] as $row)
                    @php
                        $item = $row['item'];
                        $detail = $row['detail'];
                        $isHeading = $item->loai === \App\Models\MucTieuChi::TYPE_HEADING;
                        $evidence = $row['evidence'] ?? collect();
                    @endphp
                    @if ($isHeading)
                        <tr class="table-light">
                            <td></td>
                            <td class="fw-semibold">{{ $item->ten_muc }}</td>
                            <td colspan="{{ $showHoiDong ? 7 : 5 }}"></td>
                        </tr>
                    @else
                        <tr>
                            <td class="text-secondary small">{{ $item->ma_muc }}</td>
                            <td>{{ $item->ten_muc }}</td>
                            <td class="text-center">{{ $item->diem_toi_da }}</td>
                            <td>
                                @if ($stage === 'student')
                                    {!! $noteInput($item, $detail, 'ghi_chu_sinh_vien') !!}
                                @else
                                    <div class="small">{{ $detail?->ghi_chu_sinh_vien ?: '-' }}</div>
                                @endif
                                @if ($evidence->isNotEmpty())
                                    <div class="mt-2 small">
                                        @foreach ($evidence as $file)
                                            <a class="badge text-bg-secondary text-decoration-none mr-1 mb-1" href="{{ route('minh-chung.download', $file) }}">
                                                <i class="bi bi-paperclip"></i> {{ $file->ten_file }}
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($stage === 'student')
                                    {!! $scoreInput($item, $detail, 'diem_tu_cham') !!}
                                @else
                                    {{ $scoreValue($detail, ['diem_tu_cham']) }}
                                @endif
                            </td>
                            <td>
                                @if ($stage === 'gvcn')
                                    {!! $noteInput($item, $detail, 'ghi_chu_gvcn') !!}
                                @else
                                    <div class="small">{{ $detail?->ghi_chu_gvcn ?: '-' }}</div>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($stage === 'gvcn')
                                    {!! $scoreInput($item, $detail, 'diem_gvcn', ['diem_tu_cham']) !!}
                                @else
                                    {{ $scoreValue($detail, ['diem_gvcn', 'diem_tu_cham']) }}
                                @endif
                            </td>
                            @if ($showHoiDong)
                                <td>
                                    @if ($stage === 'hoi_dong')
                                        {!! $noteInput($item, $detail, 'ghi_chu_hoi_dong') !!}
                                    @else
                                        <div class="small">{{ $detail?->ghi_chu_hoi_dong ?: '-' }}</div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($stage === 'hoi_dong')
                                        {!! $scoreInput($item, $detail, 'diem_hoi_dong', ['diem_gvcn', 'diem_tu_cham']) !!}
                                    @else
                                        {{ $scoreValue($detail, ['diem_hoi_dong', 'diem_gvcn', 'diem_tu_cham']) }}
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @endif
                @endforeach

                <tr class="fw-semibold">
                    <td></td>
                    <td>Cộng mục ({{ $indexLabel }}):</td>
                    <td class="text-center">{{ $criterion->diem_toi_da }}</td>
                    <td></td>
                    <td class="text-center">{{ $section['totals']['student'] }}</td>
                    <td></td>
                    <td class="text-center">{{ $section['totals']['gvcn'] }}</td>
                    @if ($showHoiDong)
                        <td></td>
                        <td class="text-center">{{ $section['totals']['hoi_dong'] }}</td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
