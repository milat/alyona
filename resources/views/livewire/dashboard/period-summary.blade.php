<div class="card shadow-sm mt-0 mt-md-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
            <div class="text-center mx-auto">
                @if ($monthOptions->isNotEmpty())
                    <div class="mb-1" style="min-width: 220px; max-width: 280px;">
                        <select class="form-select fw-bold fs-5" wire:model.live="selectedMonth">
                            @foreach ($monthOptions as $option)
                                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                        <div class="alyona-loading-indicator align-items-center justify-content-center gap-2 mt-2" wire:loading.flex wire:target="selectedMonth,selectMonth">
                            <span class="alyona-loading-gif" aria-hidden="true"></span>
                            <span class="small text-secondary">Carregando...</span>
                        </div>
                    </div>
                @endif
            </div>
            <div class="text-end">
                <div><strong>Gasto:</strong> R$ {{ number_format($totalSpent, 2, ',', '.') }}</div>
                <div><strong>Orçamento:</strong> R$ {{ number_format($totalBudget, 2, ',', '.') }}</div>
                @php
                    $balance = $totalBudget - $totalSpent;
                    $balanceClass = $balance >= 0 ? 'text-success' : 'text-danger';
                @endphp
                <div>
                    <strong class="text-dark">Saldo:</strong>
                    <span class="{{ $balanceClass }}">R$ {{ number_format($balance, 2, ',', '.') }}</span>
                </div>
                @if ($showNextMonthTotal && $nextMonthValue)
                    <div class="small">
                        <a href="#" class="link-secondary" wire:click.prevent="selectMonth('{{ $nextMonthValue }}')">
                            <strong>Total parcial para {{ $nextMonthLabel }}:</strong>
                            R$ {{ number_format($nextMonthTotal, 2, ',', '.') }}
                        </a>
                    </div>
                @endif
            </div>
        </div>

        @if ($rows->isEmpty())
            <div class="alert alert-info mb-0">Sem dados de gastos/orçamentos para o período atual.</div>
        @else
            <div class="row g-4">
                <div class="col-lg-6">
                    @include('livewire.dashboard.partials.bar-chart', ['chart' => $chart])
                </div>
                <div class="col-lg-6">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Categoria</th>
                                    <th class="text-end">Gasto</th>
                                    <th class="text-end">Orçamento</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $row)
                                    @php
                                        if ($row['budget'] === null) {
                                            $spentClass = 'text-dark';
                                        } else {
                                            $spentClass = $row['spent'] <= $row['budget'] ? 'text-success' : 'text-danger';
                                        }
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="d-flex align-items-center gap-2">
                                                <span class="badge" style="background: {{ $row['color'] }};">&nbsp;</span>
                                                <span>{{ $row['name'] }}</span>
                                            </span>
                                        </td>
                                        <td class="text-end text-nowrap {{ $spentClass }}">R$ {{ number_format($row['spent'], 2, ',', '.') }}</td>
                                        <td class="text-end text-nowrap">
                                            @if ($row['budget'] !== null)
                                                R$ {{ number_format($row['budget'], 2, ',', '.') }}
                                            @else
                                                <span class="text-secondary">--</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-12">
                    @include('livewire.dashboard.partials.pie-chart', ['chart' => $chart])
                </div>
            </div>
        @endif
    </div>
    @once
        <style>
            .alyona-loading-indicator {
                display: none;
            }

            .alyona-loading-gif {
                width: 1.15rem;
                height: 1.15rem;
                border: 0.18rem solid #d6d8db;
                border-top-color: #0d6efd;
                border-radius: 50%;
                animation: alyona-loading-spin 0.65s linear infinite;
            }

            @keyframes alyona-loading-spin {
                to { transform: rotate(360deg); }
            }

            .dashboard-pie-chart-wrapper {
                position: relative;
                height: 320px;
                width: 100%;
            }

            .dashboard-svg-chart text {
                font-size: 12px;
            }

            .dashboard-pie-legend {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem 1rem;
                justify-content: center;
            }

            .dashboard-pie-legend-item {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                font-size: 0.875rem;
            }

            @media (max-width: 767.98px) {
                .dashboard-pie-chart-wrapper {
                    height: 420px;
                }

                .dashboard-svg-chart {
                    min-width: 620px;
                }
            }
        </style>
    @endonce
</div>
