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

            .drl-table .form-control-sm {
                min-height: 34px;
            }

            .drl-note {
                min-width: 190px;
                resize: vertical;
            }
        </style>
    @endpush
@endonce

<div class="table-responsive">
    <table class="table table-bordered table-sm align-middle drl-table">
        <thead class="table-light">
            <tr>
                <th rowspan="2" class="text-center" style="width: 54px">TT</th>
                <th rowspan="2" style="min-width: 360px">Nội dung đánh giá</th>
                <th rowspan="2" class="text-center" style="width: 88px">Điểm tối đa</th>
                <th colspan="2" class="text-center">Cá nhân đánh giá</th>
                <th colspan="2" class="text-center">GVCN/GVCV đánh giá</th>
                @if ($showHoiDong)
                    <th colspan="2" class="text-center">CTSV/Hội đồng</th>
                @endif
            </tr>
            <tr>
                <th style="min-width: 220px">Nhận xét hoặc minh chứng</th>
                <th class="text-center" style="width: 110px">Điểm</th>
                <th style="min-width: 220px">Nhận xét hoặc minh chứng</th>
                <th class="text-center" style="width: 110px">Điểm</th>
                @if ($showHoiDong)
                    <th style="min-width: 220px">Nhận xét</th>
                    <th class="text-center" style="width: 110px">Điểm</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach ($rubric as $section)
                @php
                    $criterion = $section['criterion'];
                    $indexLabel = $roman[$loop->index] ?? $loop->iteration;
                @endphp
                <tr class="table-primary">
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
