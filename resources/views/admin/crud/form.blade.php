@extends('layouts.admin')

@section('page-title', ($item->exists ? 'Sửa ' : 'Thêm ') . $config['title'])

@section('content')
<form method="POST" action="{{ $item->exists ? route('admin.crud.update', [$module, $item->id]) : route('admin.crud.store', $module) }}" class="table-card p-3">
    @csrf
    @if ($item->exists)
        @method('PUT')
    @endif

    <div class="row g-3">
        @foreach ($config['fields'] as $name => $field)
            @php
                $type = $field['type'] ?? 'text';
                $raw = old($name, $item->{$name} ?? ($type === 'checkbox' ? true : ''));
                if ($raw instanceof \Carbon\CarbonInterface) {
                    $raw = $type === 'datetime-local' ? $raw->format('Y-m-d\TH:i') : $raw->format('Y-m-d');
                }
            @endphp
            <div class="{{ $type === 'textarea' ? 'col-12' : 'col-md-6' }}">
                @if ($type === 'checkbox')
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" id="{{ $name }}" name="{{ $name }}" value="1" @checked((bool) $raw)>
                        <label class="form-check-label" for="{{ $name }}">{{ $field['label'] }}</label>
                    </div>
                @elseif ($type === 'roles')
                    <label class="form-label">{{ $field['label'] }}</label>
                    <select class="form-select" name="roles[]" multiple>
                        @foreach ($options['roles'] as $id => $label)
                            <option value="{{ $id }}" @selected($item->exists && $item->roles->contains('id', $id))>{{ $label }}</option>
                        @endforeach
                    </select>
                @elseif ($type === 'select')
                    <label class="form-label" for="{{ $name }}">{{ $field['label'] }}</label>
                    <select class="form-select" id="{{ $name }}" name="{{ $name }}">
                        <option value="">-- Chọn --</option>
                        @foreach (($options[$field['options']] ?? []) as $id => $label)
                            <option value="{{ $id }}" @selected((string) $raw === (string) $id)>{{ $label }}</option>
                        @endforeach
                    </select>
                @elseif ($type === 'textarea')
                    <label class="form-label" for="{{ $name }}">{{ $field['label'] }}</label>
                    <textarea class="form-control" id="{{ $name }}" name="{{ $name }}" rows="4">{{ $raw }}</textarea>
                @else
                    <label class="form-label" for="{{ $name }}">{{ $field['label'] }}</label>
                    <input class="form-control" id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ $raw }}" @if($type === 'password' && $item->exists) placeholder="Để trống nếu không đổi" @endif>
                @endif
            </div>
        @endforeach
    </div>
    <div class="d-flex gap-2 mt-4">
        <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i>Lưu</button>
        <a class="btn btn-outline-secondary" href="{{ route('admin.crud.index', $module) }}">Quay lại</a>
    </div>
</form>
@endsection
