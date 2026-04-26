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
                @php
                    $labels = collect($chart['labels']);
                    $values = collect($chart['values'])->map(fn ($value) => (float) $value);
                    $pointCount = $labels->count();
                    $maxValue = max(1, (float) $values->max());
                    $width = 720;
                    $height = 340;
                    $left = 72;
                    $right = 24;
                    $top = 24;
                    $bottom = 58;
                    $plotWidth = $width - $left - $right;
                    $plotHeight = $height - $top - $bottom;
                    $baselineY = $top + $plotHeight;

                    $points = $values->values()->map(function (float $value, int $index) use ($pointCount, $left, $plotWidth, $top, $plotHeight, $maxValue) {
                        $x = $pointCount > 1
                            ? $left + (($plotWidth / ($pointCount - 1)) * $index)
                            : $left + ($plotWidth / 2);
                        $y = $top + ($plotHeight - (($value / $maxValue) * $plotHeight));

                        return [
                            'x' => round($x, 2),
                            'y' => round($y, 2),
                            'value' => $value,
                        ];
                    });

                    $linePoints = $points->map(fn (array $point) => $point['x'] . ',' . $point['y'])->implode(' ');
                    $areaPoints = $points->isNotEmpty()
                        ? $left . ',' . $baselineY . ' ' . $linePoints . ' ' . ($left + $plotWidth) . ',' . $baselineY
                        : '';
                @endphp

                @if ($pointCount === 0)
                    <div class="alert alert-info mb-0">Nenhum gasto encontrado para o filtro selecionado.</div>
                @else
                    <div class="evolution-svg-chart overflow-x-auto">
                        <svg viewBox="0 0 {{ $width }} {{ $height }}" role="img" aria-label="Evolução de gastos por período" class="w-100">
                            @for ($i = 0; $i <= 4; $i++)
                                @php
                                    $ratio = $i / 4;
                                    $gridY = $top + ($plotHeight * $ratio);
                                    $gridValue = $maxValue - ($maxValue * $ratio);
                                @endphp
                                <line x1="{{ $left }}" y1="{{ $gridY }}" x2="{{ $left + $plotWidth }}" y2="{{ $gridY }}" stroke="#e9ecef" stroke-width="1" />
                                <text x="{{ $left - 10 }}" y="{{ $gridY + 4 }}" text-anchor="end" class="evolution-axis-label" fill="#6c757d">
                                    R$ {{ number_format($gridValue, 0, ',', '.') }}
                                </text>
                            @endfor

                            <line x1="{{ $left }}" y1="{{ $top }}" x2="{{ $left }}" y2="{{ $baselineY }}" stroke="#ced4da" stroke-width="1" />
                            <line x1="{{ $left }}" y1="{{ $baselineY }}" x2="{{ $left + $plotWidth }}" y2="{{ $baselineY }}" stroke="#ced4da" stroke-width="1" />

                            <polygon points="{{ $areaPoints }}" fill="rgba(13, 110, 253, 0.14)" />
                            <polyline points="{{ $linePoints }}" fill="none" stroke="#0d6efd" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />

                            @foreach ($points as $index => $point)
                                <line x1="{{ $point['x'] }}" y1="{{ $baselineY }}" x2="{{ $point['x'] }}" y2="{{ $baselineY + 5 }}" stroke="#ced4da" stroke-width="1" />
                                <text x="{{ $point['x'] }}" y="{{ $baselineY + 24 }}" text-anchor="middle" class="evolution-month-label" fill="#495057">
                                    {{ $labels[$index] }}
                                </text>
                                <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="5" fill="#0d6efd" stroke="#ffffff" stroke-width="2">
                                    <title>{{ $labels[$index] }}: R$ {{ number_format($point['value'], 2, ',', '.') }}</title>
                                </circle>
                                <text x="{{ $point['x'] }}" y="{{ max($top + 12, $point['y'] - 12) }}" text-anchor="middle" class="evolution-value-label" fill="#0d6efd">
                                    R$ {{ number_format($point['value'], 2, ',', '.') }}
                                </text>
                            @endforeach
                        </svg>
                    </div>
                @endif
            </div>
        </div>
    @endif

    @once
        <style>
            .evolution-axis-label,
            .evolution-value-label {
                font-size: 11px;
            }

            .evolution-month-label {
                font-size: 12px;
            }

            @media (max-width: 767.98px) {
                .evolution-axis-label,
                .evolution-value-label {
                    font-size: 15px;
                    font-weight: 600;
                }

                .evolution-month-label {
                    font-size: 16px;
                    font-weight: 600;
                }
            }
        </style>
    @endonce
</div>
