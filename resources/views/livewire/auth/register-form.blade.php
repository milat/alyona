<form class="mt-4" wire:submit.prevent="register">
    <div class="mb-3">
        <label class="form-label" for="name">Nome</label>
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

    <div class="mb-3">
        <label class="form-label" for="email">Email</label>
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

    <div class="mb-3">
        <label class="form-label" for="password">Senha</label>
        <input
            id="password"
            name="password"
            type="password"
            wire:model.defer="password"
            class="form-control"
            required
        />
        @error('password')
            <div class="text-danger mt-2">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-4">
        <label class="form-label" for="password_confirmation">Confirmar senha</label>
        <input
            id="password_confirmation"
            name="password_confirmation"
            type="password"
            wire:model.defer="password_confirmation"
            class="form-control"
            required
        />
    </div>

    <button type="submit" class="btn btn-warning w-100" wire:loading.attr="disabled">
        <span wire:loading.remove>Criar usuario</span>
        <span wire:loading>
            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            Criando...
        </span>
    </button>
</form>
