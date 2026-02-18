@extends('layouts.app')

@section('title', 'Entradas')

@section('content')
    <div class="container py-5">
        <livewire:incomes.index />

        @auth
            @if (auth()->user()->household_id)
                <button
                    type="button"
                    class="btn btn-warning rounded-pill shadow position-fixed"
                    style="right: 24px; bottom: 64px; z-index: 1030;"
                    data-bs-toggle="modal"
                    data-bs-target="#incomeModal"
                >
                    <i class="bi bi-plus-circle me-1"></i> Adicionar entrada
                </button>

                <livewire:incomes.create-modal />
            @endif
        @endauth
    </div>
@endsection
