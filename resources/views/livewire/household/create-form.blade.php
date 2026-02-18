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

    <div class="mb-4">
        <label class="form-label" for="budget_period_type">Período orçamentario</label>
        <select id="budget_period_type" name="budget_period_type" class="form-select" wire:model.defer="budget_period_type" required>
            <option value="calendar_month">Do primeiro ao último dia do mês</option>
            <option value="fifth_business_day">Do quinto dia útil até um dia antes do próximo quinto dia útil</option>
        </select>
        @error('budget_period_type')
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
