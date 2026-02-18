@extends('layouts.app')

@section('title', 'Nova categoria')

@section('content')
    <div class="container py-5" style="max-width: 600px;">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h1 class="h4 fw-bold mb-0">Nova categoria</h1>
                    <a href="{{ route('categories.index') }}" class="btn btn-outline-dark btn-sm" wire:navigate>Voltar</a>
                </div>
                <p class="text-secondary">Crie uma categoria para organizar seus gastos.</p>

                <livewire:categories.create-form />
            </div>
        </div>
    </div>
@endsection
