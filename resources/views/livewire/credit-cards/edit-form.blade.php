<form class="mt-4" wire:submit.prevent="save">
    @include('livewire.credit-cards.partials.form-fields')

    <button type="submit" class="btn btn-warning w-100" wire:loading.attr="disabled">
        <span wire:loading.remove>Atualizar cartão</span>
        <span wire:loading>
            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            Atualizando...
        </span>
    </button>
</form>
