<div>
    <div class="modal fade" id="purchaseModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog">
            <div class="modal-content">
                <form wire:submit.prevent="save">
                    <div class="modal-header">
                        <h5 class="modal-title">Cadastrar compra</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        @if ($confirming)
                            <div class="border rounded p-3 mb-3">
                                <h6 class="fw-bold">Confirmar compra</h6>
                                <dl class="row mb-0">
                                    <dt class="col-5">Titulo</dt>
                                    <dd class="col-7">{{ $title }}</dd>
                                    <dt class="col-5">Descricao</dt>
                                    <dd class="col-7">{{ $description ?: '--' }}</dd>
                                    <dt class="col-5">Categoria</dt>
                                    <dd class="col-7">
                                        {{ optional($categories->firstWhere('id', $category_id))->description }}
                                        @php
                                            $remaining = $remainingByCategory[$category_id] ?? null;
                                        @endphp
                                        @if ($remaining !== null)
                                            @php
                                                $remainingClass = $remaining < 0 ? 'text-danger' : 'text-secondary';
                                            @endphp
                                            <div class="{{ $remainingClass }}">Restante: R$ {{ number_format($remaining, 2, ',', '.') }}</div>
                                        @endif
                                    </dd>
                                    <dt class="col-5">Pagamento</dt>
                                    <dd class="col-7">{{ optional($paymentMethods->firstWhere('id', $payment_method_id))->name }}</dd>
                                    <dt class="col-5">Parcelas</dt>
                                    <dd class="col-7">{{ $installments ?: 'Sem parcelamento' }}</dd>
                                    <dt class="col-5">Valor</dt>
                                    <dd class="col-7">R$ {{ number_format((float) $amount, 2, ',', '.') }}</dd>
                                    @php
                                        $effectiveInstallments = (int) ($installments ?: 1);
                                    @endphp
                                    @if ($payment_method_id == $creditMethodId && $effectiveInstallments > 1)
                                        @php
                                            $perInstallment = round((float) $amount / $effectiveInstallments, 2);
                                        @endphp
                                        <dt class="col-5">Valor da parcela</dt>
                                        <dd class="col-7">R$ {{ number_format($perInstallment, 2, ',', '.') }}</dd>
                                    @endif
                                    <dt class="col-5">Data</dt>
                                    <dd class="col-7">{{ \Carbon\Carbon::parse($purchased_at)->format('d/m/Y') }}</dd>
                                </dl>
                            </div>
                        @else
                            <div class="mb-3">
                                <label class="form-label" for="title">Titulo</label>
                                <input id="title" type="text" class="form-control" wire:model.defer="title" wire:blur="autoAssignCategoryFromTitle" required>
                                @error('title')
                                    <div class="text-danger mt-2">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="category_id">Categoria</label>
                                @php
                                    $selectedCategory = $categories->firstWhere('id', $category_id);
                                @endphp
                                <div class="dropdown w-100">
                                    <button
                                        class="btn btn-outline-secondary dropdown-toggle w-100 text-start d-flex align-items-center justify-content-between"
                                        type="button"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false"
                                    >
                                        <span class="d-inline-flex align-items-center">
                                            @if ($selectedCategory)
                                                <span class="d-inline-block rounded-circle me-2" style="width: 10px; height: 10px; background: {{ $selectedCategory->color }}; border: 1px solid #dee2e6;"></span>
                                                <span>{{ $selectedCategory->description }}</span>
                                            @else
                                                <span class="text-secondary">Selecione</span>
                                            @endif
                                        </span>
                                    </button>
                                    <ul class="dropdown-menu w-100" style="max-height: 260px; overflow-y: auto;">
                                        @foreach ($categories as $category)
                                            @php
                                                $remaining = $remainingByCategory[$category->id] ?? null;
                                            @endphp
                                            <li>
                                                <button
                                                    type="button"
                                                    class="dropdown-item d-flex justify-content-between align-items-start gap-2"
                                                    wire:click="$set('category_id', {{ $category->id }})"
                                                >
                                                    <span class="d-inline-flex align-items-center">
                                                        <span class="d-inline-block rounded-circle me-2" style="width: 10px; height: 10px; background: {{ $category->color }}; border: 1px solid #dee2e6;"></span>
                                                        <span>{{ $category->description }}</span>
                                                    </span>
                                                    <small class="text-secondary text-end">
                                                        @if ($remaining !== null)
                                                            {{ $remaining < 0 ? '-' : '' }}R$ {{ number_format(abs($remaining), 2, ',', '.') }}
                                                        @else
                                                            sem orçamento
                                                        @endif
                                                    </small>
                                                </button>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                                @error('category_id')
                                    <div class="text-danger mt-2">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label class="form-label" for="payment_method_id">Meio de pagamento</label>
                                    <select id="payment_method_id" class="form-select" wire:model.live="payment_method_id" required>
                                        <option value="">Selecione</option>
                                        @foreach ($paymentMethods as $method)
                                            <option value="{{ $method->id }}">{{ $method->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('payment_method_id')
                                        <div class="text-danger mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-6">
                                    <label class="form-label" for="installments">Parcelas</label>
                                    <select id="installments" class="form-select" wire:model.defer="installments">
                                        <option value="">Sem parcelamento</option>
                                        @for ($i = 1; $i <= 99; $i++)
                                            <option value="{{ $i }}">{{ $i }}</option>
                                        @endfor
                                    </select>
                                    @error('installments')
                                        <div class="text-danger mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="amount">Valor (R$)</label>
                                <div class="row g-2">
                                    <div class="col-8">
                                        <input
                                            id="amount"
                                            type="text"
                                            inputmode="numeric"
                                            class="form-control"
                                            wire:model.defer="amount"
                                            oninput="maskHomePurchaseAmount(this)"
                                            placeholder="0,00"
                                            required
                                        >
                                    </div>
                                    <div class="col-4">
                                        <button type="button" class="btn btn-outline-secondary w-100" wire:click="toggleCalculator">
                                            <i class="bi bi-calculator me-1"></i> Calcular
                                        </button>
                                    </div>
                                </div>
                                @error('amount')
                                    <div class="text-danger mt-2">{{ $message }}</div>
                                @enderror

                                @if ($calculatorOpen)
                                    <div class="border rounded p-2 mt-2" style="background-color: #d6d8db;">
                                        <input
                                            type="text"
                                            class="form-control form-control-sm mb-2 text-end"
                                            wire:model.live="calculatorExpression"
                                            placeholder="0"
                                        >
                                        <div class="row g-1">
                                            @foreach ([['7','8','9','/'], ['4','5','6','*'], ['1','2','3','-'], ['0','.','C','+']] as $line)
                                                @foreach ($line as $token)
                                                    <div class="col-3">
                                                        @if ($token === 'C')
                                                            <button type="button" class="btn btn-outline-danger btn-sm w-100" wire:click="clearCalculator">{{ $token }}</button>
                                                        @else
                                                            <button type="button" class="btn btn-outline-dark btn-sm w-100" wire:click="appendCalculator('{{ $token }}')">{{ $token }}</button>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            @endforeach
                                            <div class="col-6">
                                                <button type="button" class="btn btn-outline-secondary btn-sm w-100" wire:click="backspaceCalculator">⌫</button>
                                            </div>
                                            <div class="col-6">
                                                <button type="button" class="btn btn-warning btn-sm w-100" wire:click="applyCalculatorResult">Usar resultado</button>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="purchased_at">Data da compra</label>
                                <input id="purchased_at" type="date" class="form-control" wire:model.defer="purchased_at" required>
                                @error('purchased_at')
                                    <div class="text-danger mt-2">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="description">Observação</label>
                                <textarea id="description" class="form-control" wire:model.live.debounce.300ms="description" rows="2"></textarea>
                                @error('description')
                                    <div class="text-danger mt-2">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">Cancelar</button>
                        @if ($confirming)
                            <button type="button" class="btn btn-outline-secondary" wire:click="backToEdit">Editar</button>
                            <button type="submit" class="btn btn-warning" wire:loading.attr="disabled">
                                <span wire:loading.remove>Confirmar</span>
                                <span wire:loading>
                                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                    Salvando...
                                </span>
                            </button>
                        @else
                            <button type="button" class="btn btn-warning" wire:click="openConfirm" wire:loading.attr="disabled">
                                <span wire:loading.remove>Continuar</span>
                                <span wire:loading>
                                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                    Validando...
                                </span>
                            </button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@once
    <script>
        function maskHomePurchaseAmount(input) {
            const digits = (input.value || '').replace(/\D/g, '');

            if (!digits) {
                input.value = '';
                return;
            }

            const value = (parseInt(digits, 10) / 100).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

            input.value = value;
        }
    </script>
@endonce
