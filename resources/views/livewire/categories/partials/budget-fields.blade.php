<div class="row g-3 mb-4">
    <div class="col-md-6">
        <label class="form-label" for="budget_amount">Orçamento (R$)</label>
        <input
            id="budget_amount"
            name="budget_amount"
            type="text"
            inputmode="numeric"
            wire:model.defer="budget_amount"
            class="form-control"
            placeholder="0,00"
            oninput="maskCategoryBudgetAmount(this)"
        />
        @error('budget_amount')
            <div class="text-danger mt-2">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6 d-flex align-items-end">
        <small class="text-secondary">O orçamento vale a partir do momento em que for salvo.</small>
    </div>
</div>

@once
    <script>
        function maskCategoryBudgetAmount(input) {
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
