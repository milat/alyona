<nav class="navbar navbar-expand-lg bg-dark border-bottom border-body" data-bs-theme="dark">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('home') }}" wire:navigate>
            <img src="{{ asset('images/alyona.png') }}" alt="Alyona" style="height: 24px; width: auto;">
            <span>Alyona</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            @auth
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="{{ route('purchases.index') }}" wire:navigate>
                            <i class="bi bi-cart me-1"></i>Compras
                        </a>
                    </li>
{{--                    <li class="nav-item">--}}
{{--                        <a class="nav-link active" aria-current="page" href="{{ route('incomes.index') }}" wire:navigate>--}}
{{--                            <i class="bi bi-cash-coin me-1"></i>Entradas--}}
{{--                        </a>--}}
{{--                    </li>--}}
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="{{ route('categories.index') }}" wire:navigate>
                            <i class="bi bi-tags me-1"></i>Categorias
                        </a>
                    </li>
                </ul>
            @endauth
            <ul class="navbar-nav justify-content-end flex-grow-1 pe-3">
                @auth
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i>{{ auth()->user()->name }}
                            @if (auth()->user()->household)
                                <span class="text-secondary">({{ auth()->user()->household->name }})</span>
                            @else
                                <span class="text-secondary">(Sem grupo)</span>
                            @endif
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            @if (auth()->user()->household)
                                <li><a class="dropdown-item" href="{{ route('households.invitations.create') }}" wire:navigate><i class="bi bi-person-plus me-2"></i>Convidar usuario</a></li>
                                <li><hr class="dropdown-divider"></li>
                            @endif
                            <li>
                                <livewire:auth.logout-button />
                            </li>
                        </ul>
                    </li>
                @endauth
            </ul>
        </div>
    </div>
</nav>
