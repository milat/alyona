<div>
    <div class="modal fade" id="incomeModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog">
            <div class="modal-content">
                <form wire:submit.prevent="save">
                    <div class="modal-header">
                        <h5 class="modal-title">Cadastrar entrada</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label" for="income_description">Descricao</label>
                            <input id="income_description" type="text" class="form-control" wire:model.defer="description">
                            @error('description')
                                <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="income_amount">Valor (R$)</label>
                            <input
                                id="income_amount"
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
                            <label class="form-label" for="income_received_at">Data da entrada</label>
                            <input id="income_received_at" type="date" class="form-control" wire:model.defer="received_at" required>
                            @error('received_at')
                                <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning" wire:loading.attr="disabled">
                            <span wire:loading.remove>Salvar</span>
                            <span wire:loading>
                                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                Salvando...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
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
