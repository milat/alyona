@extends('layouts.app')

@section('title', 'Home')

@section('content')
    <div class="container pb-5 pt-md-5" style="padding-top: .75rem;">
        @auth
            <livewire:household.pending-invitations />

            @php
                $hasHousehold = (bool) auth()->user()->household_id;
                $hasCategories = $hasHousehold
                    ? \App\Models\Category::query()->where('household_id', auth()->user()->household_id)->exists()
                    : false;
            @endphp

            @if ($hasHousehold)
                <livewire:dashboard.period-summary />

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
            @else
                <div class="alert alert-warning">
                    Você precisa estar em um grupo para cadastrar compras.
                </div>
                <a href="{{ route('households.create') }}" class="btn btn-warning" wire:navigate>Criar grupo</a>
            @endif
        @else
            <div class="row g-3 align-items-stretch mt-1 mt-md-0">
                <div class="col-12 col-md-6 d-none d-md-block">
                    <div class="card shadow-sm h-100 home-intro-card">
                        <div class="card-body p-4 d-flex flex-column justify-content-center">
                            <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-3 mb-3 text-center text-md-start">
                                <img src="{{ asset('images/alyona.png') }}" alt="Alyona" style="height: 76px; width: auto;">
                                <h1 class="display-6 fw-bold mb-0">Alyona</h1>
                            </div>
                            <p class="text-secondary mb-0 d-none d-md-block" style="text-align: justify;">
                                Controle seus gastos e orçamentos por categoria, acompanhe relatórios por período e acompanhe a evolução mensal das despesas com visão clara do saldo disponível. Com o compartilhamento por grupo, você e sua família registram compras no mesmo ambiente e mantêm o planejamento financeiro centralizado em um só lugar.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <div class="d-flex d-md-none align-items-center justify-content-center gap-3 mb-2">
                                <img src="{{ asset('images/alyona.png') }}" alt="Alyona" style="height: 64px; width: auto;">
                                <h1 class="h2 fw-bold mb-0">Alyona</h1>
                            </div>
                            <h2 class="h4 fw-bold mb-2 text-center d-none d-md-block">Entrar</h2>
                            <livewire:auth.login-form />
                            <p class="text-center text-secondary mt-4 mb-0">
                                Não possui conta?
                                <a href="{{ route('register') }}" wire:navigate>Crie sua conta</a>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-12 d-md-none">
                    <div class="text-center mb-2">
                        <a
                            href="#aboutProjectMobile"
                            data-bs-toggle="collapse"
                            role="button"
                            aria-expanded="false"
                            aria-controls="aboutProjectMobile"
                        >
                            Sobre
                        </a>
                    </div>
                    <div class="collapse" id="aboutProjectMobile">
                        <p class="text-secondary mb-0" style="text-align: justify;">
                        Controle seus gastos e orçamentos por categoria, acompanhe relatórios por período e acompanhe a evolução mensal das despesas com visão clara do saldo disponível. Com o compartilhamento por grupo, você e sua família registram compras no mesmo ambiente e mantêm o planejamento financeiro centralizado em um só lugar.
                        </p>
                    </div>
                </div>
            </div>
        @endauth
    </div>
@endsection

@once
    <style>
        @media (max-width: 767.98px) {
            .home-intro-card {
                border: 0 !important;
                box-shadow: none !important;
                background: transparent !important;
            }
        }
    </style>
@endonce
