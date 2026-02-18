<div>
    <form class="mt-4" wire:submit.prevent="save">
        <div class="mb-3">
            <label class="form-label" for="description">Descricao</label>
            <input id="description" type="text" class="form-control" wire:model.defer="description">
            @error('description')
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
                oninput="maskIncomeAmount(this)"
                placeholder="0,00"
                required
            >
            @error('amount')
                <div class="text-danger mt-2">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label" for="received_at">Data da entrada</label>
            <input id="received_at" type="date" class="form-control" wire:model.defer="received_at" required>
            @error('received_at')
                <div class="text-danger mt-2">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn-warning w-100" wire:loading.attr="disabled">
            <span wire:loading.remove>Atualizar entrada</span>
            <span wire:loading>
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                Atualizando...
            </span>
        </button>
    </form>
</div>

@once
    <script>
        function maskIncomeAmount(input) {
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
