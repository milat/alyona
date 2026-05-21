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
                        <div class="alyona-loading-indicator align-items-center justify-content-end gap-2 mt-2" wire:loading.flex wire:target="selectedMonth">
                            <span class="alyona-loading-gif" aria-hidden="true"></span>
                            <span class="small text-secondary">Carregando...</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>

            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <button
                            type="button"
                            class="btn btn-sm {{ ($search !== '' || $selectedCategoryId !== null) ? 'btn-primary' : 'btn-outline-dark' }}"
                            onclick="togglePurchaseListPanel('purchase-search-panel', this)"
                            aria-label="Buscar compras"
                            aria-expanded="{{ $showSearch ? 'true' : 'false' }}"
                        >
                            <i class="bi bi-search"></i>
                        </button>
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-dark"
                            onclick="togglePurchaseGroupingPanel(this)"
                            aria-label="Agrupar compras"
                            aria-expanded="{{ $showGrouping ? 'true' : 'false' }}"
                        >
                            <i class="bi bi-diagram-3"></i>
                        </button>
                        <button
                            type="button"
                            class="btn btn-sm {{ ($sortBy !== 'date' || $sortDirection !== 'desc') ? 'btn-primary' : 'btn-outline-dark' }}"
                            onclick="togglePurchaseListPanel('purchase-sort-panel', this)"
                            aria-label="Ordenar compras"
                            aria-expanded="{{ $showSort ? 'true' : 'false' }}"
                        >
                            <i class="bi bi-sort-down"></i>
                        </button>
                    </div>
                    <div class="text-end">
                        <strong>Total:</strong> R$ {{ number_format($filteredTotal, 2, ',', '.') }}
                    </div>
                </div>

                <div id="purchase-grouping-panel" class="mt-2" style="display: {{ $showGrouping ? 'block' : 'none' }};">
                    @error('selectedPurchaseIds')
                        <div class="alert alert-danger py-2 mb-2">{{ $message }}</div>
                    @enderror
                    <div class="d-flex justify-content-end gap-2">
                        @if ($groupingState['canGroup'])
                            <button type="button" class="btn btn-dark btn-sm" wire:click="groupSelectedPurchases">Agrupar</button>
                        @endif
                        @if ($groupingState['canUngroup'])
                            <button type="button" class="btn btn-outline-dark btn-sm" wire:click="ungroupSelectedPurchases">Desagrupar</button>
                        @endif
                    </div>
                </div>

                <div id="purchase-sort-panel" class="mt-2" style="display: {{ $showSort ? 'block' : 'none' }};">
                    <select class="form-select form-select-sm mb-2" wire:model="sortByInput">
                        <option value="date">Data da compra</option>
                        <option value="created_at">Data de cadastro</option>
                        <option value="title">Título</option>
                        <option value="category">Categoria</option>
                        <option value="payment">Meio de pagamento</option>
                        <option value="amount">Valor</option>
                    </select>

                    <div class="d-flex gap-3 small mb-2">
                        <label class="d-flex align-items-center gap-1">
                            <input type="radio" class="form-check-input mt-0" value="asc" wire:model="sortDirectionInput">
                            Crescente
                        </label>
                        <label class="d-flex align-items-center gap-1">
                            <input type="radio" class="form-check-input mt-0" value="desc" wire:model="sortDirectionInput">
                            Decrescente
                        </label>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button
                            type="button"
                            class="btn btn-outline-danger btn-sm"
                            wire:click="clearSort"
                            aria-label="Limpar ordenação"
                        >
                            <i class="bi bi-trash"></i>
                        </button>
                        <button type="button" class="btn btn-dark btn-sm" wire:click="applySort">Ordenar</button>
                    </div>
                </div>

                <div id="purchase-search-panel" class="mt-2" style="display: {{ $showSearch ? 'block' : 'none' }};">
                    @if ($categoryOptions->isNotEmpty())
                        <select class="form-select form-select-sm mb-2" wire:model="categoryFilterInput">
                            <option value="">Todas as categorias</option>
                            @foreach ($categoryOptions as $category)
                                <option value="{{ $category->id }}">{{ $category->description }}</option>
                            @endforeach
                        </select>
                    @endif
                    <input
                        type="search"
                        class="form-control form-control-sm"
                        placeholder="Buscar por data, título, categoria, pagamento ou valor"
                        wire:model="searchInput"
                    >
                    <div class="mt-2 d-flex justify-content-end gap-2">
                        <button
                            type="button"
                            class="btn btn-outline-danger btn-sm"
                            wire:click="clearFilters"
                            aria-label="Limpar filtros"
                        >
                            <i class="bi bi-trash"></i>
                        </button>
                        <button type="button" class="btn btn-dark btn-sm" wire:click="applyFilters">Buscar</button>
                    </div>
                </div>
            </div>

        @if ($purchases->isEmpty())
            <div class="alert alert-info">Nenhuma compra encontrada.</div>
        @else
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th class="purchase-grouping-cell" style="display: {{ $showGrouping ? 'table-cell' : 'none' }}; width: 1%;"></th>
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
                                <td class="purchase-grouping-cell" style="display: {{ $showGrouping ? 'table-cell' : 'none' }};">
                                    @php
                                        $groupingMode = $groupingState['mode'];
                                        $isGroupedPurchase = $purchase->purchase_group_id !== null;
                                        $disableGroupingCheckbox = ($groupingMode === 'grouped' && ! $isGroupedPurchase)
                                            || ($groupingMode === 'ungrouped' && $isGroupedPurchase);
                                    @endphp
                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        value="{{ $purchase->id }}"
                                        wire:model.live="selectedPurchaseIds"
                                        aria-label="Selecionar compra {{ $purchase->title }}"
                                        @disabled($disableGroupingCheckbox)
                                    >
                                </td>
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
                                <td>
                                    @if ($purchase->creditCard)
                                        Crédito ({{ $purchase->creditCard->title }})
                                    @else
                                        {{ $purchase->paymentMethod?->name }}
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    R$ {{ number_format($purchase->amount, 2, ',', '.') }}
                                    @if ($purchase->purchase_group_id)
                                        <div class="text-secondary" style="font-size: 0.68rem;">
                                            AGRP {{ $purchase->purchase_group_id }}: R$ {{ number_format((float) ($groupTotals[$purchase->purchase_group_id] ?? 0), 2, ',', '.') }}
                                        </div>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex align-items-center gap-1 flex-nowrap">
                                        <a
                                            href="{{ route('purchases.edit', ['purchase' => $purchase, 'mes' => $selectedMonth]) }}"
                                            class="btn btn-outline-dark btn-sm"
                                            wire:navigate
                                            aria-label="Editar compra"
                                            title="Editar"
                                        >
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button
                                            type="button"
                                            class="btn btn-outline-danger btn-sm"
                                            wire:click="delete({{ $purchase->id }})"
                                            wire:loading.attr="disabled"
                                            onclick="if(!confirm('Tem certeza que deseja excluir esta compra?')){event.stopImmediatePropagation();}"
                                            aria-label="Excluir compra"
                                            title="Excluir"
                                        >
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
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
                {{ $purchases->onEachSide(1)->links('vendor.pagination.bootstrap-5-pt') }}
            </div>
        @endif
    @endif

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
        </style>
        <script>
            function togglePurchaseListPanel(panelId, button) {
                const panel = document.getElementById(panelId);

                if (!panel) {
                    return;
                }

                const shouldShow = panel.style.display === 'none' || panel.style.display === '';
                panel.style.display = shouldShow ? 'block' : 'none';

                if (button) {
                    button.setAttribute('aria-expanded', shouldShow ? 'true' : 'false');
                }

                return shouldShow;
            }


            function togglePurchaseGroupingPanel(button) {
                const panel = document.getElementById('purchase-grouping-panel');

                if (!panel) {
                    return;
                }

                const shouldShow = panel.style.display === 'none' || panel.style.display === '';
                panel.style.display = shouldShow ? 'block' : 'none';
                document.querySelectorAll('.purchase-grouping-cell').forEach((cell) => {
                    cell.style.display = shouldShow ? 'table-cell' : 'none';
                });

                if (button) {
                    button.setAttribute('aria-expanded', shouldShow ? 'true' : 'false');
                }

                const componentRoot = button ? button.closest('[wire\\:id]') : null;
                const componentId = componentRoot ? componentRoot.getAttribute('wire:id') : null;

                if (componentId && window.Livewire) {
                    window.Livewire.find(componentId).set('showGrouping', shouldShow);
                }

                return shouldShow;
            }
        </script>
    @endonce
</div>
