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

    <div class="form-check form-switch mb-4">
        <input
            id="hide_from_home_chart"
            name="hide_from_home_chart"
            type="checkbox"
            class="form-check-input"
            wire:model.defer="hide_from_home_chart"
        />
        <label class="form-check-label" for="hide_from_home_chart">Ocultar no gráfico da home</label>
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
                data-bs-content="Quando uma compra possuir o mesmo título, esta categoria será selecionada automaticamente."
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

    @once
        <script>
            const initCategoryInfoPopovers = () => {
                document.querySelectorAll('[data-bs-toggle="popover"]').forEach((el) => {
                    bootstrap.Popover.getOrCreateInstance(el);
                });
            };

            document.addEventListener('livewire:navigated', () => {
                initCategoryInfoPopovers();
            });

            document.addEventListener('DOMContentLoaded', () => {
                initCategoryInfoPopovers();
            });
        </script>
    @endonce

    @include('livewire.categories.partials.budget-fields')

    <button type="submit" class="btn btn-warning w-100" wire:loading.attr="disabled">
        <span wire:loading.remove>Salvar categoria</span>
        <span wire:loading>
            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            Salvando...
        </span>
    </button>
</form>
