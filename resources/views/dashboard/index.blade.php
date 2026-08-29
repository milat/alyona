@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="container py-5">
        <livewire:dashboard.period-summary />

        @php
            $hasCategories = \App\Models\Category::query()
                ->where('household_id', auth()->user()->household_id)
                ->exists();
        @endphp

        <button
            type="button"
            class="btn btn-warning rounded-pill shadow position-fixed"
            style="right: 24px; bottom: 64px; z-index: 1030;"
            data-bs-toggle="modal"
            data-bs-target="#purchaseModal"
            @if (! $hasCategories) disabled @endif
        >
            <i class="bi bi-plus-circle me-1"></i> Adicionar compra
        </button>

        <livewire:purchases.create-modal />
    </div>
@endsection
