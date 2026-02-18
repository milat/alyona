@extends('layouts.app')

@section('title', 'Criar grupo')

@section('content')
    <div class="container py-5" style="max-width: 600px;">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 fw-bold">Criar grupo</h1>
                <p class="text-secondary">Defina um nome para o grupo que vai compartilhar os gastos.</p>

                <livewire:household.create-form />
            </div>
        </div>
    </div>
@endsection
