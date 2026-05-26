<div>
    @if (! auth()->user() || auth()->user()->household_id === null)
        <div class="alert alert-warning">
            Você precisa estar em um grupo para gerar relatórios.
        </div>
        <a href="{{ route('households.create') }}" class="btn btn-warning" wire:navigate>Criar grupo</a>
    @else
        <div class="mb-3">
            <h1 class="h4 fw-bold mb-0">Relatório</h1>
        </div>

        <form class="card shadow-sm mb-4" wire:submit.prevent="generate">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label" for="dateFrom">De</label>
                        <input id="dateFrom" type="date" class="form-control" wire:model.defer="dateFrom" required>
                        @error('dateFrom')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="dateTo">Até</label>
                        <input id="dateTo" type="date" class="form-control" wire:model.defer="dateTo" required>
                        @error('dateTo')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Categorias</label>
                        <div class="dropdown">
                            <button class="btn btn-outline-dark dropdown-toggle w-100 text-start" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                Categorias
                            </button>
                            <div class="dropdown-menu p-3 w-100" style="max-height: 280px; overflow-y: auto;">
                                @forelse ($categories as $category)
                                    <label class="dropdown-item d-flex align-items-center gap-2 px-0">
                                        <input type="checkbox" class="form-check-input m-0" value="{{ $category->id }}" wire:model.defer="selectedCategories">
                                        <span>{{ $category->description }}</span>
                                    </label>
                                @empty
                                    <div class="text-secondary small">Nenhuma categoria cadastrada.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Meios de pagamento</label>
                        <div class="dropdown">
                            <button class="btn btn-outline-dark dropdown-toggle w-100 text-start" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                Meios de pagamento
                            </button>
                            <div class="dropdown-menu p-3 w-100" style="max-height: 280px; overflow-y: auto;">
                                @forelse ($paymentOptions as $paymentOption)
                                    <label class="dropdown-item d-flex align-items-center gap-2 px-0">
                                        <input type="checkbox" class="form-check-input m-0" value="{{ $paymentOption['value'] }}" wire:model.defer="selectedPayments">
                                        <span>{{ $paymentOption['label'] }}</span>
                                    </label>
                                @empty
                                    <div class="text-secondary small">Nenhum meio de pagamento disponível.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-3 text-end">
                    <button type="submit" class="btn btn-dark" wire:loading.attr="disabled" wire:target="generate">
                        <span wire:loading.remove wire:target="generate">Gerar</span>
                        <span wire:loading wire:target="generate">
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            Gerando...
                        </span>
                    </button>
                </div>
            </div>
        </form>

        @if ($generated)
            <div class="mb-3 text-center fs-4 fw-bold">
                Total: R$ {{ number_format($total, 2, ',', '.') }}
            </div>

            @if ($purchases->isEmpty())
                <div class="alert alert-info">Nenhuma compra encontrada para os filtros informados.</div>
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
                                                data-bs-target="#report-purchase-desc-{{ $purchase->id }}"
                                                aria-label="Ver observação"
                                            >
                                                <i class="bi bi-question-circle"></i>
                                            </button>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($purchase->category)
                                            @php
                                                $hasSubcategories = $purchase->categoryAllocations->isNotEmpty();
                                            @endphp
                                            <div class="d-inline-flex align-items-start">
                                                <span class="badge" style="background: {{ $purchase->category->color }}; color: #000;">
                                                    {{ $purchase->category->description }}
                                                </span>
                                                @if ($hasSubcategories)
                                                    <button
                                                        type="button"
                                                        class="btn btn-link btn-sm p-0 ms-1 flex-shrink-0 align-baseline"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#report-purchase-categories-{{ $purchase->id }}"
                                                        aria-label="Ver valores por categoria"
                                                    >
                                                        <i class="bi bi-question-circle"></i>
                                                    </button>
                                                @endif
                                            </div>
                                            @if ($hasSubcategories)
                                                @foreach ($purchase->categoryAllocations as $allocation)
                                                    <div class="mt-1">
                                                        <span class="badge" style="background: {{ $allocation->category?->color ?? '#e9ecef' }}; color: #000;">
                                                            {{ $allocation->category?->description ?? '-' }}
                                                        </span>
                                                    </div>
                                                @endforeach
                                            @endif
                                        @else
                                            <span class="text-secondary">--</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($purchase->creditCard)
                                            Crédito
                                        @else
                                            {{ $purchase->paymentMethod?->name }}
                                        @endif
                                    </td>
                                    <td class="text-end text-nowrap">R$ {{ number_format((float) ($purchase->report_amount ?? $purchase->amount), 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @foreach ($purchases as $purchase)
                    @if ($purchase->description)
                        <div class="modal fade" id="report-purchase-desc-{{ $purchase->id }}" tabindex="-1" aria-hidden="true" wire:ignore.self>
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Observação da compra</h5>
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

                    @if ($purchase->categoryAllocations->isNotEmpty())
                        <div class="modal fade" id="report-purchase-categories-{{ $purchase->id }}" tabindex="-1" aria-hidden="true" wire:ignore.self>
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Valores por categoria</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="d-flex justify-content-between gap-3 mb-2">
                                            <span>{{ $purchase->category?->description ?? '-' }}</span>
                                            <strong>R$ {{ number_format($purchase->primaryCategoryAmount(), 2, ',', '.') }}</strong>
                                        </div>
                                        @foreach ($purchase->categoryAllocations as $allocation)
                                            <div class="d-flex justify-content-between gap-3 mb-2">
                                                <span>{{ $allocation->category?->description ?? '-' }}</span>
                                                <strong>R$ {{ number_format((float) $allocation->amount, 2, ',', '.') }}</strong>
                                            </div>
                                        @endforeach
                                        <hr>
                                        <div class="d-flex justify-content-between gap-3 mb-0">
                                            <span>Total</span>
                                            <strong>R$ {{ number_format((float) $purchase->amount, 2, ',', '.') }}</strong>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">Fechar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            @endif
        @endif
    @endif
</div>
