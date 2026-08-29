<div>
    <h1 class="h4 fw-bold mb-3">Visão geral</h1>

    <div class="row g-3">
        @foreach ($cards as $card)
            <div class="col-12 col-md-6">
                <a
                    href="{{ $card['url'] }}"
                    class="card h-100 shadow-sm text-decoration-none text-body home-summary-card"
                    wire:navigate
                >
                    <div class="card-body p-4 d-flex align-items-center gap-3">
                        <span class="rounded-circle bg-warning-subtle text-dark d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                            <i class="bi {{ $card['icon'] }} fs-4"></i>
                        </span>
                        <div class="flex-grow-1">
                            <div class="text-secondary mb-1">{{ $card['title'] }}</div>
                            @if ($card['value_label'])
                                <div class="small text-secondary">{{ $card['value_label'] }}</div>
                            @endif
                            <div class="h4 fw-bold mb-0 {{ $card['value_class'] }}">R$ {{ number_format($card['total'], 2, ',', '.') }}</div>
                        </div>
                        <i class="bi bi-chevron-right text-secondary"></i>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    @once
        <style>
            .home-summary-card {
                transition: transform .15s ease, box-shadow .15s ease;
            }

            .home-summary-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15) !important;
            }

            .home-balance-warning {
                color: #fd7e14;
            }
        </style>
    @endonce
</div>
