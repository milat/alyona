@extends('layouts.app')

@section('title', 'Relatório')

@section('content')
    <div class="container py-4">
        <livewire:reports.purchases-report />
    </div>
@endsection
