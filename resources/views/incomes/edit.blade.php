@extends('layouts.app')

@section('title', 'Editar entrada')

@section('content')
    <div class="container py-5" style="max-width: 600px;">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h1 class="h4 fw-bold mb-0">Editar entrada</h1>
                    <a href="{{ route('incomes.index') }}" class="btn btn-outline-dark btn-sm" wire:navigate>Voltar</a>
                </div>
                <p class="text-secondary">Atualize os dados da entrada.</p>

                <livewire:incomes.edit-form :incomeId="$income->id" />
            </div>
        </div>
    </div>
@endsection
