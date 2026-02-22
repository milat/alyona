<div>
    @if ($invitations->isNotEmpty())
        <div class="card border-warning-subtle mb-3">
            <div class="card-body">
                <h2 class="h6 fw-bold mb-3">Convites pendentes</h2>

                <div class="d-flex flex-column gap-2">
                    @foreach ($invitations as $invitation)
                        <div class="border rounded p-3">
                            <div class="mb-2">
                                <div><strong>Grupo:</strong> {{ $invitation->household?->name ?? '-' }}</div>
                                <div class="text-secondary small"><strong>Convidado por:</strong> {{ $invitation->inviter?->name ?? '-' }}</div>
                            </div>

                            <div class="d-flex gap-2">
                                <button
                                    type="button"
                                    class="btn btn-success btn-sm"
                                    wire:click="accept({{ $invitation->id }})"
                                    wire:loading.attr="disabled"
                                >
                                    Aceitar
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-outline-danger btn-sm"
                                    wire:click="reject({{ $invitation->id }})"
                                    wire:loading.attr="disabled"
                                >
                                    Recusar
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>
