<button type="button" class="{{ $class }}" wire:click="logout" wire:loading.attr="disabled">
    <span wire:loading.remove>{{ $label }}</span>
    <span wire:loading>
        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
        Saindo...
    </span>
</button>
