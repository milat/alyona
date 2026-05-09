@extends('layouts.app')

@section('title', 'Novo cartão')

@section('content')
    <div class="container py-5" style="max-width: 600px;">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h1 class="h4 fw-bold mb-0">Novo cartão</h1>
                    <a href="{{ route('credit-cards.index') }}" class="btn btn-outline-dark btn-sm" wire:navigate>Voltar</a>
                </div>
                <p class="text-secondary">Cadastre um cartão de crédito do grupo.</p>

                <livewire:credit-cards.create-form />
            </div>
        </div>
    </div>
@endsection
