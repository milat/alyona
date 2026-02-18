@extends('layouts.app')

@section('title', 'Criar usuario')

@section('content')
    <div class="container py-5" style="max-width: 520px;">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 fw-bold">Criar conta</h1>
                <p class="text-secondary">Cadastre-se para comecar.</p>

                <livewire:auth.register-form />

                <p class="text-center text-secondary mt-4 mb-0">
                    Ja tem conta?
                    <a href="{{ route('home') }}" class="link-dark fw-semibold" wire:navigate>Entrar</a>
                </p>
            </div>
        </div>
    </div>
@endsection
