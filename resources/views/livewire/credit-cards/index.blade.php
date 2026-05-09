<div>
    @if (! auth()->user() || auth()->user()->household_id === null)
        <div class="alert alert-warning">
            Você precisa estar em um grupo para cadastrar cartões.
        </div>
        <a href="{{ route('households.create') }}" class="btn btn-warning" wire:navigate>Criar grupo</a>
    @else
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 fw-bold mb-0">Cartões</h1>
            <a href="{{ route('credit-cards.create') }}" class="btn btn-warning" wire:navigate>Adicionar cartão</a>
        </div>

        @if ($creditCards->isEmpty())
            <div class="alert alert-info">Nenhum cartão cadastrado ainda.</div>
        @else
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Fechamento</th>
                            <th class="text-end">Limite</th>
                            <th>Status</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($creditCards as $creditCard)
                            <tr>
                                <td>
                                    {{ $creditCard->title }}
                                    @if ($creditCard->observation)
                                        <div class="small text-secondary">{{ $creditCard->observation }}</div>
                                    @endif
                                </td>
                                <td>Dia {{ $creditCard->closing_day }}</td>
                                <td class="text-end text-nowrap">
                                    @if ($creditCard->limit !== null)
                                        R$ {{ number_format((float) $creditCard->limit, 2, ',', '.') }}
                                    @else
                                        <span class="text-secondary">--</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($creditCard->is_active)
                                        <span class="badge text-bg-success">Ativo</span>
                                    @else
                                        <span class="badge text-bg-secondary">Inativo</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('credit-cards.edit', $creditCard) }}" class="btn btn-outline-dark btn-sm" wire:navigate>Editar</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endif
</div>
