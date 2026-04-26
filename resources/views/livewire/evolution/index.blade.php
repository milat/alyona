<div>
    @if (! auth()->user() || auth()->user()->household_id === null)
        <div class="alert alert-warning">
            Você precisa estar em um grupo para visualizar a evolução.
        </div>
        <a href="{{ route('households.create') }}" class="btn btn-warning" wire:navigate>Criar grupo</a>
    @else
        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h1 class="h4 fw-bold mb-0">Evolução</h1>
                <div style="min-width: 220px;">
                    <select class="form-select form-select-sm" wire:model.live="selectedCategoryId">
                        <option value="">Todas as categorias</option>
                        @foreach ($categoryOptions as $category)
                            <option value="{{ $category->id }}">{{ $category->description }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <div
                    id="evolution-chart-data"
                    wire:key="evolution-chart-data-{{ $selectedCategoryId ?: 'all' }}-{{ md5(json_encode($chart['values'])) }}"
                    data-labels='@json($chart['labels'])'
                    data-values='@json($chart['values'])'
                ></div>
                <div id="evolutionLineChartWrapper" style="height: 340px;" wire:ignore>
                    <canvas id="evolutionLineChart"></canvas>
                </div>
            </div>
        </div>
    @endif

    @once
        <script>
            window.renderEvolutionLineChart = function (chartData = null) {
                const dataEl = document.getElementById('evolution-chart-data');
                const wrapper = document.getElementById('evolutionLineChartWrapper');

                if (!dataEl || !wrapper || typeof Chart === 'undefined') {
                    return;
                }

                const labels = chartData?.labels || JSON.parse(dataEl.dataset.labels || '[]');
                const values = chartData?.values || JSON.parse(dataEl.dataset.values || '[]');

                if (window.evolutionLineChartInstance) {
                    window.evolutionLineChartInstance.destroy();
                }

                wrapper.innerHTML = '<canvas id="evolutionLineChart"></canvas>';
                const canvas = document.getElementById('evolutionLineChart');

                window.evolutionLineChartInstance = new Chart(canvas.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Gastos',
                                data: values,
                                borderColor: '#0d6efd',
                                backgroundColor: 'rgba(13, 110, 253, 0.18)',
                                pointBackgroundColor: '#0d6efd',
                                pointBorderColor: '#0d6efd',
                                pointRadius: 4,
                                pointHoverRadius: 5,
                                tension: 0.25,
                                fill: true,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function (context) {
                                        const value = Number(context.raw || 0);

                                        return `Gastos: R$ ${value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function (value) {
                                        return 'R$ ' + Number(value).toLocaleString('pt-BR');
                                    }
                                }
                            }
                        }
                    }
                });
            };

            window.scheduleEvolutionLineChartRender = function (chartData = null) {
                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        window.renderEvolutionLineChart(chartData);
                    });
                });

                setTimeout(() => {
                    window.renderEvolutionLineChart(chartData);
                }, 150);
            };

            document.addEventListener('livewire:navigated', window.renderEvolutionLineChart);
            document.addEventListener('DOMContentLoaded', window.renderEvolutionLineChart);
            document.addEventListener('livewire:init', () => {
                Livewire.hook('morphed', ({ el }) => {
                    if (el && (el.id === 'evolution-chart-data' || el.querySelector?.('#evolution-chart-data'))) {
                        window.scheduleEvolutionLineChartRender();
                    }
                });

                Livewire.on('evolution-chart-updated', (payload) => {
                    const chartData = payload?.chart || payload?.[0]?.chart || payload;

                    window.scheduleEvolutionLineChartRender(chartData);
                });
            });
        </script>
    @endonce
</div>
