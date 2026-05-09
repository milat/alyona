@php
    $labels = collect($chart['labels']);
    $spent = collect($chart['spent'])->map(fn ($value) => (float) $value);
    $budget = collect($chart['budget'])->map(fn ($value) => (float) $value);
    $spentColors = collect($chart['spentColors']);
    $count = $labels->count();
    $maxValue = max(1, (float) $spent->merge($budget)->max());
    $width = 720;
    $height = 300;
    $left = 68;
    $right = 24;
    $top = 22;
    $bottom = 72;
    $plotWidth = $width - $left - $right;
    $plotHeight = $height - $top - $bottom;
    $baselineY = $top + $plotHeight;
    $groupWidth = $count > 0 ? $plotWidth / $count : $plotWidth;
    $barWidth = min(34, max(12, $groupWidth * 0.26));
@endphp

@if ($count === 0)
    <div class="alert alert-info mb-0">Sem dados para o gráfico.</div>
@else
    <div class="overflow-x-auto">
        <svg viewBox="0 0 {{ $width }} {{ $height }}" role="img" aria-label="Gastos e orçamento por categoria" class="dashboard-svg-chart w-100">
            @for ($i = 0; $i <= 4; $i++)
                @php
                    $ratio = $i / 4;
                    $gridY = $top + ($plotHeight * $ratio);
                    $gridValue = $maxValue - ($maxValue * $ratio);
                @endphp
                <line x1="{{ $left }}" y1="{{ $gridY }}" x2="{{ $left + $plotWidth }}" y2="{{ $gridY }}" stroke="#e9ecef" stroke-width="1" />
                <text x="{{ $left - 10 }}" y="{{ $gridY + 4 }}" text-anchor="end" fill="#6c757d">
                    R$ {{ number_format($gridValue, 0, ',', '.') }}
                </text>
            @endfor

            <line x1="{{ $left }}" y1="{{ $baselineY }}" x2="{{ $left + $plotWidth }}" y2="{{ $baselineY }}" stroke="#ced4da" stroke-width="1" />

            @foreach ($labels as $index => $label)
                @php
                    $centerX = $left + ($groupWidth * $index) + ($groupWidth / 2);
                    $spentValue = (float) ($spent[$index] ?? 0);
                    $budgetValue = (float) ($budget[$index] ?? 0);
                    $spentHeight = ($spentValue / $maxValue) * $plotHeight;
                    $budgetHeight = ($budgetValue / $maxValue) * $plotHeight;
                    $spentX = $centerX - $barWidth - 2;
                    $budgetX = $centerX + 2;
                    $spentY = $baselineY - $spentHeight;
                    $budgetY = $baselineY - $budgetHeight;
                    $labelText = \Illuminate\Support\Str::limit($label, 14);
                @endphp

                <rect x="{{ $spentX }}" y="{{ $spentY }}" width="{{ $barWidth }}" height="{{ max(1, $spentHeight) }}" fill="{{ $spentColors[$index] ?? '#0d6efd' }}">
                    <title>{{ $label }} - Gasto: R$ {{ number_format($spentValue, 2, ',', '.') }}</title>
                </rect>
                <rect x="{{ $budgetX }}" y="{{ $budgetY }}" width="{{ $barWidth }}" height="{{ max(1, $budgetHeight) }}" fill="#adb5bd">
                    <title>{{ $label }} - Orçamento: R$ {{ number_format($budgetValue, 2, ',', '.') }}</title>
                </rect>
                <text x="{{ $centerX }}" y="{{ $baselineY + 22 }}" text-anchor="middle" fill="#495057">
                    {{ $labelText }}
                </text>
            @endforeach
        </svg>
    </div>
@endif
