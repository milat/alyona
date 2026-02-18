@extends('layouts.app')

@section('title', 'Convidar usuario')

@section('content')
    <div class="container py-5" style="max-width: 600px;">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 fw-bold">Convidar usuario</h1>
                <p class="text-secondary">Informe o email cadastrado do usuario que deseja convidar.</p>

                <livewire:household.invite-form />
            </div>
        </div>
    </div>
@endsection
