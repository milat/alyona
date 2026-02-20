<div>
    @if (! auth()->user() || auth()->user()->household_id === null)
        <div class="alert alert-warning">
            Você precisa estar em um grupo para cadastrar compras.
        </div>
        <a href="{{ route('households.create') }}" class="btn btn-warning" wire:navigate>Criar grupo</a>
    @else
        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h1 class="h4 fw-bold mb-0">Compras</h1>
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
            @if ($categoryOptions->isNotEmpty())
                <div class="mt-2">
                    <div class="ms-auto" style="min-width: 220px;">
                        <select class="form-select form-select-sm" wire:model.live="selectedCategoryId">
                            <option value="">Todas as categorias</option>
                            @foreach ($categoryOptions as $category)
                                <option value="{{ $category->id }}">{{ $category->description }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif
        </div>

        @if ($purchases->isEmpty())
            <div class="alert alert-info">Nenhuma compra cadastrada ainda.</div>
        @else
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Título</th>
                            <th>Categoria</th>
                            <th>Pagamento</th>
                            <th class="text-end">Valor</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($purchases as $purchase)
                            <tr>
                                <td>
                                    {{ $purchase->purchased_at->format('d/m/Y') }}
                                    <div class="small text-secondary">{{ $purchase->user?->name ?? '-' }}</div>
                                </td>
                                <td>
                                    {{ $purchase->title }}
                                    @if ($purchase->description)
                                        <button
                                            type="button"
                                            class="btn btn-link btn-sm p-0 ms-1 align-baseline"
                                            data-bs-toggle="modal"
                                            data-bs-target="#purchase-desc-{{ $purchase->id }}"
                                            aria-label="Ver descricao"
                                        >
                                            <i class="bi bi-question-circle"></i>
                                        </button>
                                    @endif
                                </td>
                                <td>
                                    @if ($purchase->category)
                                        <span class="badge" style="background: {{ $purchase->category->color }}; color: #000;">
                                            {{ $purchase->category->description }}
                                        </span>
                                    @else
                                        <span class="text-secondary">--</span>
                                    @endif
                                </td>
                                <td>{{ $purchase->paymentMethod?->name }}</td>
                                <td class="text-end text-nowrap">R$ {{ number_format($purchase->amount, 2, ',', '.') }}</td>
                                <td class="text-end">
                                    <button
                                        type="button"
                                        class="btn btn-outline-danger btn-sm"
                                        wire:click="delete({{ $purchase->id }})"
                                        wire:loading.attr="disabled"
                                        onclick="if(!confirm('Tem certeza que deseja excluir esta compra?')){event.stopImmediatePropagation();}"
                                    >
                                        Excluir
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @foreach ($purchases as $purchase)
                @if ($purchase->description)
                    <div class="modal fade" id="purchase-desc-{{ $purchase->id }}" tabindex="-1" aria-hidden="true" wire:ignore.self>
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Descricao da compra</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                </div>
                                <div class="modal-body">
                                    <p class="mb-0">{{ $purchase->description }}</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">Fechar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
            <div class="mt-3">
                {{ $purchases->links('vendor.pagination.bootstrap-5-pt') }}
            </div>
        @endif
    @endif
</div>
