@extends('layouts.sinh-vien')

@section('page-title', 'Hoạt động')

@section('content')
<div class="row g-3">
    @forelse ($activities as $activity)
        <div class="col-md-6 col-xl-4">
            @include('student.activities.partials.card', [
                'activity' => $activity,
                'registered' => in_array($activity->id, $registeredIds, true),
            ])
        </div>
    @empty
        <div class="col-12"><div class="table-card p-4 text-center text-secondary">Chưa có hoạt động phù hợp.</div></div>
    @endforelse
</div>
<div class="mt-3">{{ $activities->links() }}</div>
@endsection
