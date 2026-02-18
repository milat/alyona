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

    @include('livewire.categories.partials.budget-fields')

    <button type="submit" class="btn btn-warning w-100" wire:loading.attr="disabled">
        <span wire:loading.remove>Salvar categoria</span>
        <span wire:loading>
            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            Salvando...
        </span>
    </button>
</form>
