<form class="mt-4" wire:submit.prevent="save">
    <div class="mb-3">
        <label class="form-label" for="currentStartDate">{{ ucfirst($currentLabel) }}</label>
        <input id="currentStartDate" type="date" class="form-control" wire:model.defer="currentStartDate" required>
        @error('currentStartDate')
            <div class="text-danger mt-2">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-4">
        <label class="form-label" for="nextStartDate">{{ ucfirst($nextLabel) }}</label>
        <input id="nextStartDate" type="date" class="form-control" wire:model.defer="nextStartDate" required>
        @error('nextStartDate')
            <div class="text-danger mt-2">{{ $message }}</div>
        @enderror
    </div>

    <button type="submit" class="btn btn-warning w-100" wire:loading.attr="disabled">
        <span wire:loading.remove>Salvar configurações</span>
        <span wire:loading>
            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            Salvando...
        </span>
    </button>
</form>
