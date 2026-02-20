@extends('layouts.app')

@section('title', 'Configurar 5º dia útil')

@section('content')
    <div class="container py-5" style="max-width: 640px;">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 fw-bold">Configurar 5º dia útil</h1>
                <p class="text-secondary mb-0">Ajuste apenas o mês atual e o próximo mês.</p>

                <livewire:household.period-start-settings-form />
            </div>
        </div>
    </div>
@endsection
