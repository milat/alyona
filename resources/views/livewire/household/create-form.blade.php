<form class="mt-4" wire:submit.prevent="create">
    <div class="mb-3">
        <label class="form-label" for="name">Nome do grupo</label>
        <input
            id="name"
            name="name"
            type="text"
            wire:model.defer="name"
            class="form-control"
            required
        />
        @error('name')
            <div class="text-danger mt-2">{{ $message }}</div>
        @enderror
    </div>

    <button type="submit" class="btn btn-warning w-100" wire:loading.attr="disabled">
        <span wire:loading.remove>Criar grupo</span>
        <span wire:loading>
            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            Criando...
        </span>
    </button>
</form>
