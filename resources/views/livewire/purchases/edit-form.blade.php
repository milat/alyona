<form class="mt-4" wire:submit.prevent="save">
    <div class="mb-3">
        <label class="form-label" for="title">Título</label>
        <input id="title" type="text" class="form-control" wire:model.defer="title" required>
        @error('title')
            <div class="text-danger mt-2">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label class="form-label" for="category_id">Categoria</label>
        <select id="category_id" class="form-select" wire:model.live="category_id" required>
            <option value="">Selecione</option>
            @foreach ($categories as $category)
                @php
                    $isCategoryUsedAsSubcategory = collect($subcategories)->pluck('category_id')->map(fn ($id) => (int) $id)->contains($category->id);
                @endphp
                <option value="{{ $category->id }}" @disabled($isCategoryUsedAsSubcategory)>{{ $category->description }}</option>
            @endforeach
        </select>
        @error('category_id')
            <div class="text-danger mt-2">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label class="form-label" for="payment_option">Meio de pagamento</label>
        <select id="payment_option" class="form-select" wire:model.defer="payment_option" required>
            <option value="">Selecione</option>
            @foreach ($paymentOptions as $option)
                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
            @endforeach
        </select>
        @error('payment_option')
            <div class="text-danger mt-2">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label class="form-label" for="amount">Valor (R$)</label>
        <input
            id="amount"
            type="text"
            inputmode="numeric"
            class="form-control"
            wire:model.defer="amount"
            oninput="maskPurchaseEditAmount(this)"
            placeholder="0,00"
            required
        >
        @error('amount')
            <div class="text-danger mt-2">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="form-label mb-0">Subcategorias</label>
            <button type="button" class="btn btn-outline-dark btn-sm" wire:click="addSubcategory">Adicionar</button>
        </div>
        @error('subcategories')
            <div class="text-danger mt-2">{{ $message }}</div>
        @enderror
        @foreach ($subcategories as $index => $subcategory)
            <div class="row g-2 align-items-start mb-2">
                <div class="col-6">
                    <select class="form-select" wire:model.live="subcategories.{{ $index }}.category_id">
                        <option value="">Subcategoria</option>
                        @foreach ($categories as $category)
                            @php
                                $selectedSubcategoryIds = collect($subcategories)
                                    ->except($index)
                                    ->pluck('category_id')
                                    ->map(fn ($id) => (int) $id);
                                $isCategoryUnavailable = $category->id === (int) $category_id || $selectedSubcategoryIds->contains($category->id);
                            @endphp
                            <option value="{{ $category->id }}" @disabled($isCategoryUnavailable)>{{ $category->description }}</option>
                        @endforeach
                    </select>
                    @error('subcategories.' . $index . '.category_id')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-4">
                    <input type="text" inputmode="numeric" class="form-control" placeholder="0,00" wire:model.defer="subcategories.{{ $index }}.amount" oninput="maskPurchaseEditAmount(this)">
                    @error('subcategories.' . $index . '.amount')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-2">
                    <button type="button" class="btn btn-outline-danger w-100" wire:click="removeSubcategory({{ $index }})" aria-label="Remover subcategoria">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mb-3">
        <label class="form-label" for="purchased_at">Data da compra</label>
        <input id="purchased_at" type="date" class="form-control" wire:model.defer="purchased_at" required>
        @error('purchased_at')
            <div class="text-danger mt-2">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-4">
        <label class="form-label" for="description">Observação</label>
        <textarea id="description" class="form-control" wire:model.defer="description" rows="3"></textarea>
        @error('description')
            <div class="text-danger mt-2">{{ $message }}</div>
        @enderror
    </div>

    <button type="submit" class="btn btn-warning w-100" wire:loading.attr="disabled">
        <span wire:loading.remove>Atualizar compra</span>
        <span wire:loading>
            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            Atualizando...
        </span>
    </button>

    @once
        <script>
            function maskPurchaseEditAmount(input) {
                const digits = (input.value || '').replace(/\D/g, '');

                if (!digits) {
                    input.value = '';
                    return;
                }

                input.value = (parseInt(digits, 10) / 100).toLocaleString('pt-BR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }
        </script>
    @endonce
</form>
