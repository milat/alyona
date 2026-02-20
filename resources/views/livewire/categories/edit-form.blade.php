<div>
    <form class="mt-4" wire:submit.prevent="save">
        <div class="mb-3">
            <label class="form-label" for="description">Descrição</label>
            <input
                id="description"
                name="description"
                type="text"
                wire:model.defer="description"
                class="form-control"
                required
            />
            @error('description')
                <div class="text-danger mt-2">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-4">
            <label class="form-label">Cor</label>
            @include('livewire.categories.partials.color-options')
            @error('color')
                <div class="text-danger mt-2">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-check form-switch mb-4">
            <input
                id="is_active"
                name="is_active"
                type="checkbox"
                class="form-check-input"
                wire:model.defer="is_active"
            />
            <label class="form-check-label" for="is_active">Ativa</label>
        </div>

        <div class="mb-4">
            <label class="form-label d-flex align-items-center gap-2" for="default_purchase_description">
                <span>Título padrão da compra</span>
                <button
                    type="button"
                    class="btn btn-link p-0 text-secondary"
                    data-bs-toggle="popover"
                    data-bs-trigger="focus"
                    data-bs-placement="top"
                    data-bs-content="Quando uma compra tiver o mesmo título, esta categoria será selecionada automaticamente."
                    aria-label="Ajuda sobre título padrão da compra"
                >
                    <i class="bi bi-info-circle"></i>
                </button>
            </label>
            <input
                id="default_purchase_description"
                name="default_purchase_description"
                type="text"
                wire:model.defer="default_purchase_description"
                class="form-control"
                maxlength="255"
                placeholder="Opcional"
            />
            @error('default_purchase_description')
                <div class="text-danger mt-2">{{ $message }}</div>
            @enderror
        </div>

        @include('livewire.categories.partials.budget-fields')

        <button type="submit" class="btn btn-warning w-100" wire:loading.attr="disabled">
            <span wire:loading.remove>Atualizar categoria</span>
            <span wire:loading>
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                Atualizando...
            </span>
        </button>
    </form>

    @if ($history->isNotEmpty())
        <div class="mt-4">
            <h2 class="h6 fw-bold">Histórico de orçamentos</h2>
            <div class="table-responsive">
                <table class="table table-sm table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th class="text-end">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($history as $budget)
                            <tr>
                                <td>{{ ($budget->effective_at ?? $budget->created_at)->format('d/m/Y') }}</td>
                                <td class="text-end">R$ {{ number_format($budget->amount, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
