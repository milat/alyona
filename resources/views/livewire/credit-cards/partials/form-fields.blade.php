<div class="mb-3">
    <label class="form-label" for="title">Título</label>
    <input
        id="title"
        name="title"
        type="text"
        wire:model.defer="title"
        class="form-control"
        required
    />
    @error('title')
        <div class="text-danger mt-2">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label" for="closing_day">Dia fechamento</label>
    <input
        id="closing_day"
        name="closing_day"
        type="number"
        min="1"
        max="31"
        step="1"
        wire:model.defer="closing_day"
        class="form-control"
        required
    />
    @error('closing_day')
        <div class="text-danger mt-2">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label" for="limit">Limite</label>
    <input
        id="limit"
        name="limit"
        type="text"
        inputmode="numeric"
        wire:model.defer="limit"
        oninput="maskCreditCardLimit(this)"
        class="form-control"
        placeholder="Opcional"
    />
    @error('limit')
        <div class="text-danger mt-2">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label" for="observation">Observação</label>
    <textarea
        id="observation"
        name="observation"
        wire:model.defer="observation"
        class="form-control"
        rows="3"
    ></textarea>
    @error('observation')
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
    <label class="form-check-label" for="is_active">Ativo</label>
    @error('is_active')
        <div class="text-danger mt-2">{{ $message }}</div>
    @enderror
</div>

@once
    <script>
        function maskCreditCardLimit(input) {
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
