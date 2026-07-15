@extends('layouts.admin')

@section('page-title', $config['title'])

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="h5 mb-0">{{ $config['title'] }}</h2>
        <div class="text-secondary small">Phân hệ quản trị dữ liệu</div>
    </div>
    @if (count($config['fields']))
        <a class="btn btn-primary" href="{{ route('admin.crud.create', $module) }}"><i class="bi bi-plus-lg me-1"></i>Thêm mới</a>
    @endif
</div>

<div class="table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Mã</th>
                    @foreach ($config['columns'] as $column)
                        <th>{{ $config['column_labels'][$column] ?? config("ui.columns.$column", str($column)->replace('_', ' ')->title()) }}</th>
                    @endforeach
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    <tr>
                        <td>{{ $item->id }}</td>
                        @foreach ($config['columns'] as $column)
                            @php
                                $value = data_get($item, $column);

                                if ($value instanceof \Carbon\CarbonInterface) {
                                    $value = $value->format('d/m/Y H:i');
                                } elseif ($column === 'is_active') {
                                    $value = config('ui.active.' . (int) (bool) $value);
                                } elseif (in_array($column, ['trang_thai', 'status'], true)) {
                                    $value = config("ui.statuses.$value", $value);
                                } elseif ($column === 'guard_name') {
                                    $value = config("ui.guards.$value", $value);
                                } elseif ($module === 'roles' && $column === 'name') {
                                    $value = config("ui.roles.$value", $value);
                                } elseif ($module === 'permissions' && $column === 'name') {
                                    $value = config("ui.permissions.$value", $value);
                                } elseif (is_bool($value)) {
                                    $value = config('ui.booleans.' . (int) $value);
                                }
                            @endphp
                            <td>{{ $value }}</td>
                        @endforeach
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.crud.show', [$module, $item->id]) }}"><i class="bi bi-eye"></i></a>
                            @if ($module === 'users' && method_exists($item, 'trashed') && $item->trashed())
                                <form method="POST" action="{{ route('admin.users.restore', $item->id) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-success" type="submit" title="Khôi phục"><i class="bi bi-arrow-counterclockwise"></i></button>
                                </form>
                            @elseif (count($config['fields']))
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.crud.edit', [$module, $item->id]) }}"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('admin.crud.destroy', [$module, $item->id]) }}" class="d-inline" onsubmit="return confirm('Xóa dữ liệu này?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ count($config['columns']) + 2 }}" class="text-center text-secondary py-4">Chưa có dữ liệu</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $items->links() }}</div>
@endsection
