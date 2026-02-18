<form class="mt-4" wire:submit.prevent="send">
    <div class="mb-4">
        <label class="form-label" for="email">Email do usuário</label>
        <input
            id="email"
            name="email"
            type="email"
            wire:model.defer="email"
            class="form-control"
            required
        />
        @error('email')
            <div class="text-danger mt-2">{{ $message }}</div>
        @enderror
    </div>

    <button type="submit" class="btn btn-warning w-100" wire:loading.attr="disabled">
        <span wire:loading.remove>Enviar convite</span>
        <span wire:loading>
            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            Enviando...
        </span>
    </button>
</form>
