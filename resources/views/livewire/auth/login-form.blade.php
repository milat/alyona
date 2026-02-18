<form class="mt-4" wire:submit.prevent="login">
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

    <div class="form-check mb-4">
        <input class="form-check-input" type="checkbox" wire:model="remember" id="remember">
        <label class="form-check-label" for="remember">Manter conectado</label>
    </div>

    <button type="submit" class="btn btn-warning w-100" wire:loading.attr="disabled">
        <span wire:loading.remove>Entrar</span>
        <span wire:loading>
            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            Entrando...
        </span>
    </button>
</form>
