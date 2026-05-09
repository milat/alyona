@php
    $labels = collect($chart['pieLabels']);
    $values = collect($chart['pieSpent'])->map(fn ($value) => (float) $value);
    $colors = collect($chart['pieCategoryColors']);
    $total = (float) $values->sum();
    $size = 320;
    $center = $size / 2;
    $radius = 64;
    $strokeWidth = 128;
    $circumference = 2 * pi() * $radius;
    $dashOffset = 0;
@endphp

@if ($labels->isEmpty() || $total <= 0)
    <div class="alert alert-info mb-0">Sem gastos para o gráfico de pizza.</div>
@else
    <div class="dashboard-pie-chart-wrapper">
        <svg viewBox="0 0 {{ $size }} {{ $size }}" role="img" aria-label="Distribuição percentual dos gastos por categoria" class="h-100 w-100">
            <circle
                cx="{{ $center }}"
                cy="{{ $center }}"
                r="{{ $radius }}"
                fill="none"
                stroke="#f8f9fa"
                stroke-width="{{ $strokeWidth }}"
            />
            @foreach ($values as $index => $value)
                @php
                    $percent = $total > 0 ? ($value / $total) * 100 : 0;
                    $dashLength = ($value / $total) * $circumference;
                    $gapLength = $circumference - $dashLength;
                    $currentDashOffset = -$dashOffset;
                    $dashOffset += $dashLength;
                @endphp

                @if ($value > 0)
                    <circle
                        cx="{{ $center }}"
                        cy="{{ $center }}"
                        r="{{ $radius }}"
                        fill="none"
                        stroke="{{ $colors[$index] ?? '#adb5bd' }}"
                        stroke-width="{{ $strokeWidth }}"
                        stroke-dasharray="{{ $dashLength }} {{ $gapLength }}"
                        stroke-dashoffset="{{ $currentDashOffset }}"
                        transform="rotate(-90 {{ $center }} {{ $center }})"
                    >
                        <title>{{ $labels[$index] }}: {{ number_format($percent, 1, ',', '.') }}% (R$ {{ number_format($value, 2, ',', '.') }})</title>
                    </circle>
                @endif
            @endforeach
        </svg>
    </div>

    <div class="dashboard-pie-legend mt-3">
        @foreach ($labels as $index => $label)
            @php
                $value = (float) ($values[$index] ?? 0);
                $percent = $total > 0 ? ($value / $total) * 100 : 0;
            @endphp
            <span class="dashboard-pie-legend-item">
                <span class="d-inline-block rounded-circle" style="width: 10px; height: 10px; background: {{ $colors[$index] ?? '#adb5bd' }}; border: 1px solid #dee2e6;"></span>
                <span>{{ $label }} ({{ number_format($percent, 1, ',', '.') }}%)</span>
            </span>
        @endforeach
    </div>
@endif
