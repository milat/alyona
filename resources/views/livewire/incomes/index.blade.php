<div>
    @if (! auth()->user() || auth()->user()->household_id === null)
        <div class="alert alert-warning">
            Voce precisa estar em um grupo para cadastrar entradas.
        </div>
        <a href="{{ route('households.create') }}" class="btn btn-warning" wire:navigate>Criar grupo</a>
    @else
        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h1 class="h4 fw-bold mb-0">Entradas</h1>
                @if ($monthOptions->isNotEmpty())
                    <div style="min-width: 220px;">
                        <select class="form-select form-select-sm" wire:model.live="selectedMonth">
                            @foreach ($monthOptions as $option)
                                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>
        </div>

        @if ($incomes->isEmpty())
            <div class="alert alert-info">Nenhuma entrada cadastrada ainda.</div>
        @else
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Descricao</th>
                            <th class="text-end">Valor</th>
                            <th class="text-end">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($incomes as $income)
                            <tr>
                                <td>
                                    {{ $income->received_at->format('d/m/Y') }}
                                    <div class="small text-secondary">{{ $income->user?->name ?? '-' }}</div>
                                </td>
                                <td>{{ $income->description ?: '--' }}</td>
                                <td class="text-end text-nowrap">R$ {{ number_format($income->amount, 2, ',', '.') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('incomes.edit', $income) }}" class="btn btn-outline-dark btn-sm" wire:navigate>Editar</a>
                                    <button
                                        type="button"
                                        class="btn btn-outline-danger btn-sm"
                                        wire:click="delete({{ $income->id }})"
                                        wire:loading.attr="disabled"
                                        onclick="if(!confirm('Tem certeza que deseja excluir esta entrada?')){event.stopImmediatePropagation();}"
                                    >
                                        Excluir
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $incomes->links('vendor.pagination.bootstrap-5-pt') }}
            </div>
        @endif
    @endif
</div>
