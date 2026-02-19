<div>
    @if (! auth()->user() || auth()->user()->household_id === null)
        <div class="alert alert-warning">
            Voce precisa estar em um grupo para cadastrar categorias.
        </div>
        <a href="{{ route('households.create') }}" class="btn btn-warning" wire:navigate>Criar grupo</a>
    @else
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 fw-bold mb-0">Categorias</h1>
            <a href="{{ route('categories.create') }}" class="btn btn-warning" wire:navigate>Adicionar categoria</a>
        </div>

        <div class="alert alert-light border mb-3">
            Orçamento total:
            <strong>R$ {{ number_format($activeBudgetTotal, 2, ',', '.') }}</strong>
        </div>

        @if ($categories->isEmpty())
            <div class="alert alert-info">Nenhuma categoria cadastrada ainda.</div>
        @else
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Categoria</th>
                            <th>Orçamento</th>
                            <th>Status</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($categories as $category)
                            @php
                                $budget = $category->budgets->first();
                            @endphp
                            <tr>
                                <td>
                                    <span class="d-inline-block rounded-circle me-2 align-middle" style="width: 12px; height: 12px; background: {{ $category->color }}; border: 1px solid #dee2e6;"></span>
                                    <span class="align-middle">{{ $category->description }}</span>
                                </td>
                                <td class="text-nowrap">
                                    @if ($budget)
                                        R$ {{ number_format($budget->amount, 2, ',', '.') }}
                                    @else
                                        <span class="text-secondary">--</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($category->is_active)
                                        <span class="badge text-bg-success">Ativa</span>
                                    @else
                                        <span class="badge text-bg-secondary">Inativa</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('categories.edit', $category) }}" class="btn btn-outline-dark btn-sm" wire:navigate>Editar</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endif
</div>
