@extends('layouts.admin')

@section('page-title', 'Chi tiết ' . $config['title'])

@section('content')
<div class="table-card p-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">#{{ $item->id }}</h2>
        <a class="btn btn-outline-secondary" href="{{ route('admin.crud.index', $module) }}">Quay lại</a>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered mb-0">
            @foreach ($item->getAttributes() as $key => $value)
                @php
                    $label = $config['column_labels'][$key] ?? config("ui.columns.$key", str($key)->replace('_', ' ')->title());

                    if ($value instanceof \Carbon\CarbonInterface) {
                        $value = $value->format('d/m/Y H:i');
                    } elseif ($key === 'is_active') {
                        $value = config('ui.active.' . (int) (bool) $value);
                    } elseif (in_array($key, ['auto_cong_diem', 'is_bat_buoc'], true)) {
                        $value = config('ui.booleans.' . (int) (bool) $value);
                    } elseif (in_array($key, ['trang_thai', 'status'], true)) {
                        $value = config("ui.statuses.$value", $value);
                    } elseif ($key === 'guard_name') {
                        $value = config("ui.guards.$value", $value);
                    } elseif ($module === 'roles' && $key === 'name') {
                        $value = config("ui.roles.$value", $value);
                    } elseif ($module === 'permissions' && $key === 'name') {
                        $value = config("ui.permissions.$value", $value);
                    } elseif (is_bool($value)) {
                        $value = config('ui.booleans.' . (int) $value);
                    }
                @endphp
                <tr>
                    <th class="table-light" style="width: 220px">{{ $label }}</th>
                    <td>{{ is_array($value) ? json_encode($value) : $value }}</td>
                </tr>
            @endforeach
        </table>
    </div>
</div>
@endsection
