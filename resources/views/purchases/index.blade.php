@extends('layouts.app')

@section('title', 'Compras')

@section('content')
    <div class="container py-5">
        <livewire:purchases.index />

        @auth
            @php
                $hasHousehold = (bool) auth()->user()->household_id;
                $hasCategories = $hasHousehold
                    ? \App\Models\Category::query()->where('household_id', auth()->user()->household_id)->exists()
                    : false;
            @endphp

            @if ($hasHousehold)
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
            @endif
        @endauth
    </div>
@endsection
