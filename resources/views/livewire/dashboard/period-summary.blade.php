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
                    </div>
                @endif
                <p class="text-secondary mb-0 small">{{ $periodRangeLabel }}</p>
                <hr class="my-2">
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
            </div>
        </div>

        @if ($rows->isEmpty())
            <div class="alert alert-info mb-0">Sem dados de gastos/orçamentos para o período atual.</div>
        @else
            <div class="row g-4">
                <div class="col-lg-6">
                    <div id="dashboard-chart-data"
                         data-labels='@json($chart['labels'])'
                         data-spent='@json($chart['spent'])'
                         data-budget='@json($chart['budget'])'
                         data-spent-colors='@json($chart['spentColors'])'>
                    </div>
                    <div style="height: 280px;">
                        <canvas id="dashboardPeriodChart"></canvas>
                    </div>
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
                                            <span class="badge me-2" style="background: {{ $row['color'] }};">&nbsp;</span>
                                            {{ $row['name'] }}
                                        </td>
                                        <td class="text-end {{ $spentClass }}">R$ {{ number_format($row['spent'], 2, ',', '.') }}</td>
                                        <td class="text-end">
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
            </div>
        @endif
    </div>
</div>

@once
    <script>
        window.renderDashboardPeriodChart = function () {
            const dataEl = document.getElementById('dashboard-chart-data');
            const canvas = document.getElementById('dashboardPeriodChart');

            if (!dataEl || !canvas || typeof Chart === 'undefined') {
                return;
            }

            const labels = JSON.parse(dataEl.dataset.labels || '[]');
            const spent = JSON.parse(dataEl.dataset.spent || '[]');
            const budget = JSON.parse(dataEl.dataset.budget || '[]');
            const spentColors = JSON.parse(dataEl.dataset.spentColors || '[]');

            if (window.dashboardPeriodChartInstance) {
                window.dashboardPeriodChartInstance.destroy();
            }

            if (!labels.length) {
                window.dashboardPeriodChartInstance = null;
                return;
            }

            window.dashboardPeriodChartInstance = new Chart(canvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Gasto',
                            data: spent,
                            backgroundColor: spentColors,
                        },
                        {
                            label: 'Orcamento',
                            data: budget,
                            backgroundColor: '#adb5bd',
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        };

        document.addEventListener('livewire:navigated', window.renderDashboardPeriodChart);
        document.addEventListener('DOMContentLoaded', window.renderDashboardPeriodChart);
        document.addEventListener('livewire:init', () => {
            Livewire.hook('morph.updated', ({ el }) => {
                if (el && (el.id === 'dashboard-chart-data' || el.querySelector?.('#dashboard-chart-data'))) {
                    requestAnimationFrame(() => {
                        window.renderDashboardPeriodChart();
                    });
                }
            });

            Livewire.on('dashboard-period-changed', () => {
                requestAnimationFrame(() => {
                    window.renderDashboardPeriodChart();
                });
            });
        });
    </script>
@endonce
