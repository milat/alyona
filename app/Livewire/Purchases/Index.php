<?php

namespace App\Livewire\Purchases;

use App\Models\Category;
use App\Models\Purchase;
use App\Models\PurchaseGroup;
use App\Support\BudgetPeriod;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url(as: 'mes', except: null)]
    public ?string $selectedMonth = null;

    #[Url(as: 'categoria', except: null)]
    public ?string $selectedCategoryId = null;

    #[Url(as: 'busca', except: '')]
    public string $search = '';

    public ?string $categoryFilterInput = null;

    public string $searchInput = '';

    public bool $showSearch = false;

    public bool $showSort = false;

    public bool $showGrouping = false;

    public array $selectedPurchaseIds = [];

    public string $sortBy = 'date';

    public string $sortDirection = 'desc';

    public string $sortByInput = 'date';

    public string $sortDirectionInput = 'desc';

    public function mount(): void
    {
        $this->categoryFilterInput = $this->selectedCategoryId;
        $this->searchInput = $this->search;
        $this->sortByInput = $this->sortBy;
        $this->sortDirectionInput = $this->sortDirection;
    }

    #[On('purchase-saved')]
    public function refreshPurchases(): void
    {
        // Livewire re-renders the component after handling the event.
    }

    public function delete(int $purchaseId): void
    {
        $user = auth()->user();

        $purchase = Purchase::query()
            ->where('id', $purchaseId)
            ->where('household_id', $user->household_id)
            ->firstOrFail();

        $purchase->delete();

        session()->flash('success', 'Compra excluida com sucesso.');
    }

    public function updatedSelectedMonth(): void
    {
        $this->resetPage();
    }

    public function toggleSearch(): void
    {
        $this->showSearch = ! $this->showSearch;
    }

    public function toggleSort(): void
    {
        $this->showSort = ! $this->showSort;
    }

    public function groupSelectedPurchases(): void
    {
        $user = auth()->user();

        if (! $user || $user->household_id === null) {
            return;
        }

        $ids = $this->selectedPurchaseIds();

        if (count($ids) < 2) {
            $this->showGrouping = true;
            $this->addError('selectedPurchaseIds', 'Selecione pelo menos duas compras para agrupar.');
            return;
        }

        $purchases = Purchase::query()
            ->where('household_id', $user->household_id)
            ->whereIn('id', $ids)
            ->get(['id', 'purchase_group_id']);

        if ($purchases->count() !== count($ids)) {
            $this->showGrouping = true;
            $this->addError('selectedPurchaseIds', 'Selecione apenas compras válidas deste grupo.');
            return;
        }

        if ($purchases->contains(fn (Purchase $purchase) => $purchase->purchase_group_id !== null)) {
            $this->showGrouping = true;
            $this->addError('selectedPurchaseIds', 'Uma ou mais compras selecionadas já estão agrupadas.');
            return;
        }

        $group = PurchaseGroup::create([
            'household_id' => $user->household_id,
        ]);

        Purchase::query()
            ->where('household_id', $user->household_id)
            ->whereIn('id', $ids)
            ->update(['purchase_group_id' => $group->id]);

        $this->selectedPurchaseIds = [];
        $this->showGrouping = false;
        session()->flash('success', 'Compras agrupadas com sucesso.');
    }

    public function ungroupSelectedPurchases(): void
    {
        $user = auth()->user();

        if (! $user || $user->household_id === null) {
            return;
        }

        $ids = $this->selectedPurchaseIds();

        if ($ids === []) {
            $this->showGrouping = true;
            $this->addError('selectedPurchaseIds', 'Selecione ao menos uma compra para desagrupar.');
            return;
        }

        $groupIds = Purchase::query()
            ->where('household_id', $user->household_id)
            ->whereIn('id', $ids)
            ->whereNotNull('purchase_group_id')
            ->pluck('purchase_group_id')
            ->unique()
            ->values();

        if ($groupIds->isEmpty()) {
            $this->showGrouping = true;
            $this->addError('selectedPurchaseIds', 'As compras selecionadas não estão agrupadas.');
            return;
        }

        Purchase::query()
            ->where('household_id', $user->household_id)
            ->whereIn('id', $ids)
            ->update(['purchase_group_id' => null]);

        $this->deleteEmptyPurchaseGroups($user->household_id, $groupIds);

        $this->selectedPurchaseIds = [];
        $this->showGrouping = false;
        session()->flash('success', 'Agrupamento removido com sucesso.');
    }

    public function applyFilters(): void
    {
        $this->selectedCategoryId = $this->categoryFilterInput ?: null;
        $this->search = trim($this->searchInput);
        $this->showSearch = false;
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->selectedCategoryId = null;
        $this->categoryFilterInput = null;
        $this->search = '';
        $this->searchInput = '';
        $this->showSearch = false;
        $this->resetPage();
    }

    public function applySort(): void
    {
        $allowedSorts = ['date', 'created_at', 'title', 'category', 'payment', 'amount'];
        $allowedDirections = ['asc', 'desc'];

        $this->sortBy = in_array($this->sortByInput, $allowedSorts, true) ? $this->sortByInput : 'date';
        $this->sortDirection = in_array($this->sortDirectionInput, $allowedDirections, true) ? $this->sortDirectionInput : 'desc';
        $this->showSort = false;
        $this->resetPage();
    }

    public function clearSort(): void
    {
        $this->sortBy = 'date';
        $this->sortDirection = 'desc';
        $this->sortByInput = 'date';
        $this->sortDirectionInput = 'desc';
        $this->showSort = false;
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();
        $purchases = collect();
        $monthOptions = collect();
        $categoryOptions = collect();
        $filteredTotal = 0;
        $groupTotals = collect();
        $groupingState = [
            'mode' => null,
            'canGroup' => false,
            'canUngroup' => false,
        ];

        if ($user && $user->household_id !== null) {
            $household = $user->household;
            $monthOptions = $this->buildMonthOptions($household);
            $currentMonth = BudgetPeriod::currentPeriodMonth($household);
            $hasCurrentMonth = $monthOptions->contains(fn (array $item) => $item['value'] === $currentMonth);

            if ($monthOptions->isNotEmpty()) {
                if ($this->selectedMonth === null) {
                    $this->selectedMonth = $hasCurrentMonth
                        ? $currentMonth
                        : $monthOptions->first()['value'];
                } elseif (! $monthOptions->contains(fn (array $item) => $item['value'] === $this->selectedMonth)) {
                    $this->selectedMonth = $hasCurrentMonth
                        ? $currentMonth
                        : $monthOptions->first()['value'];
                }
            }

            $categoryIdsWithPurchases = collect();

            if ($this->selectedMonth) {
                [$year, $month] = explode('-', $this->selectedMonth);
                $period = BudgetPeriod::forYearMonth($household, (int) $year, (int) $month);

                $categoryIdsWithPurchases = Purchase::query()
                    ->where('household_id', $user->household_id)
                    ->whereBetween('reference_date', [
                        $period['start']->toDateString(),
                        $period['end']->toDateString(),
                    ])
                    ->distinct()
                    ->pluck('category_id');
            }

            $categoryUsageStart = now()->copy()->subDays(90)->toDateString();
            $categoryUsageSubquery = Purchase::query()
                ->selectRaw('category_id, COUNT(*) as usage_count')
                ->where('household_id', $user->household_id)
                ->whereDate('purchased_at', '>=', $categoryUsageStart)
                ->groupBy('category_id');

            $categoryOptions = Category::query()
                ->leftJoinSub($categoryUsageSubquery, 'purchase_category_usage', function ($join) {
                    $join->on('categories.id', '=', 'purchase_category_usage.category_id');
                })
                ->where('categories.household_id', $user->household_id)
                ->whereIn('categories.id', $categoryIdsWithPurchases)
                ->orderByRaw('COALESCE(purchase_category_usage.usage_count, 0) DESC')
                ->orderByDesc('categories.is_active')
                ->orderBy('categories.description')
                ->select('categories.id', 'categories.description', 'categories.is_active')
                ->get();

            if (
                $this->selectedCategoryId !== null
                && ! $categoryOptions->contains(fn (Category $category) => (string) $category->id === $this->selectedCategoryId)
            ) {
                $this->selectedCategoryId = null;
                $this->categoryFilterInput = null;
            }

            $query = Purchase::query()
                ->with(['category', 'paymentMethod', 'creditCard', 'user'])
                ->where('purchases.household_id', $user->household_id);

            if ($this->selectedMonth) {
                [$year, $month] = explode('-', $this->selectedMonth);
                $period = BudgetPeriod::forYearMonth($household, (int) $year, (int) $month);
                $query->whereBetween('purchases.reference_date', [
                    $period['start']->toDateString(),
                    $period['end']->toDateString(),
                ]);
            }

            if ($this->selectedCategoryId !== null) {
                $query->where('purchases.category_id', (int) $this->selectedCategoryId);
            }

            $this->applySearch($query);

            $filteredTotal = (float) (clone $query)->sum('purchases.amount');
            $this->applySorting($query);
            $purchases = $query->paginate(100)->withQueryString();
            $groupTotals = $this->groupTotalsFor($purchases->getCollection());
            $groupingState = $this->groupingStateFor($purchases->getCollection());

            if ($purchases->isEmpty() && $this->hasActiveFilters()) {
                $this->showSearch = true;
            }
        }

        return view('livewire.purchases.index', [
            'purchases' => $purchases,
            'monthOptions' => $monthOptions,
            'categoryOptions' => $categoryOptions,
            'filteredTotal' => $filteredTotal,
            'groupTotals' => $groupTotals,
            'groupingState' => $groupingState,
        ]);
    }


    private function selectedPurchaseIds(): array
    {
        return collect($this->selectedPurchaseIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }


    private function groupingStateFor(Collection $purchases): array
    {
        $selectedIds = $this->selectedPurchaseIds();

        if ($selectedIds === []) {
            return [
                'mode' => null,
                'canGroup' => false,
                'canUngroup' => false,
            ];
        }

        $selectedPurchases = $purchases->whereIn('id', $selectedIds);
        $groupedCount = $selectedPurchases->filter(fn (Purchase $purchase) => $purchase->purchase_group_id !== null)->count();
        $ungroupedCount = $selectedPurchases->filter(fn (Purchase $purchase) => $purchase->purchase_group_id === null)->count();
        $mode = $groupedCount > 0 ? 'grouped' : 'ungrouped';

        return [
            'mode' => $mode,
            'canGroup' => $mode === 'ungrouped' && $ungroupedCount >= 2,
            'canUngroup' => $mode === 'grouped' && $groupedCount >= 1,
        ];
    }

    private function groupTotalsFor(Collection $purchases): Collection
    {
        $groupIds = $purchases
            ->pluck('purchase_group_id')
            ->filter()
            ->unique()
            ->values();

        if ($groupIds->isEmpty()) {
            return collect();
        }

        return Purchase::query()
            ->selectRaw('purchase_group_id, SUM(amount) as total')
            ->whereIn('purchase_group_id', $groupIds)
            ->groupBy('purchase_group_id')
            ->pluck('total', 'purchase_group_id');
    }

    private function deleteEmptyPurchaseGroups(int $householdId, Collection $groupIds): void
    {
        $remainingGroupIds = Purchase::query()
            ->whereIn('purchase_group_id', $groupIds)
            ->pluck('purchase_group_id')
            ->unique();

        PurchaseGroup::query()
            ->where('household_id', $householdId)
            ->whereIn('id', $groupIds->diff($remainingGroupIds))
            ->delete();
    }

    private function buildMonthOptions($household): Collection
    {
        $currentMonth = BudgetPeriod::currentPeriodMonth($household);
        $currentMonthDate = Carbon::createFromFormat('Y-m', $currentMonth);
        $windowStart = $currentMonthDate->copy()->subMonthsNoOverflow(12)->format('Y-m');
        $windowEnd = $currentMonthDate->copy()->addMonthsNoOverflow(3)->format('Y-m');

        $periodMonths = Purchase::query()
            ->where('household_id', $household->id)
            ->orderByDesc('reference_date')
            ->get(['reference_date'])
            ->toBase()
            ->map(fn (Purchase $purchase) => BudgetPeriod::forHousehold($household, $purchase->reference_date)['period_month'])
            ->push($currentMonth)
            ->unique()
            ->filter(fn (string $value) => $value >= $windowStart && $value <= $windowEnd)
            ->sortDesc()
            ->values();

        return $periodMonths->map(function (string $value) {
            $date = Carbon::createFromFormat('Y-m', $value);

            return [
                'value' => $value,
                'label' => $this->formatMonthLabel($date),
            ];
        });
    }

    private function formatMonthLabel(Carbon $date): string
    {
        $months = [
            1 => 'Janeiro',
            2 => 'Fevereiro',
            3 => 'Março',
            4 => 'Abril',
            5 => 'Maio',
            6 => 'Junho',
            7 => 'Julho',
            8 => 'Agosto',
            9 => 'Setembro',
            10 => 'Outubro',
            11 => 'Novembro',
            12 => 'Dezembro',
        ];

        return $months[(int) $date->format('n')] . ' / ' . $date->format('Y');
    }


    private function hasActiveFilters(): bool
    {
        return $this->selectedCategoryId !== null || trim($this->search) !== '';
    }


    private function applySorting($query): void
    {
        $direction = $this->sortDirection === 'asc' ? 'asc' : 'desc';

        match ($this->sortBy) {
            'date' => $query
                ->orderBy('purchases.purchased_at', $direction)
                ->orderBy('purchases.created_at', 'desc'),
            'created_at' => $query
                ->orderBy('purchases.created_at', $direction),
            'title' => $query
                ->orderBy('purchases.title', $direction)
                ->orderBy('purchases.created_at', 'desc'),
            'category' => $query
                ->select('purchases.*')
                ->leftJoin('categories as sort_categories', 'sort_categories.id', '=', 'purchases.category_id')
                ->orderBy('sort_categories.description', $direction)
                ->orderBy('purchases.created_at', 'desc'),
            'payment' => $query
                ->select('purchases.*')
                ->leftJoin('payment_methods as sort_payment_methods', 'sort_payment_methods.id', '=', 'purchases.payment_method_id')
                ->leftJoin('credit_cards as sort_credit_cards', 'sort_credit_cards.id', '=', 'purchases.credit_card_id')
                ->orderByRaw('COALESCE(sort_credit_cards.title, sort_payment_methods.name) ' . $direction)
                ->orderBy('purchases.created_at', 'desc'),
            'amount' => $query
                ->orderBy('purchases.amount', $direction)
                ->orderBy('purchases.created_at', 'desc'),
            default => $query->orderBy('purchases.created_at', 'desc'),
        };
    }

    private function applySearch($query): void
    {
        $term = trim($this->search);

        if ($term === '') {
            return;
        }

        $like = '%' . $term . '%';
        $date = $this->normalizeSearchDate($term);
        $formattedDate = $this->formattedDateSearch($term);
        $partialDate = $this->partialDateSearch($term);
        $amount = $this->normalizeSearchAmount($term);
        $creditCardTitle = $this->extractCreditCardTitleFromPaymentSearch($term);

        $query->where(function ($searchQuery) use ($like, $date, $formattedDate, $partialDate, $amount, $creditCardTitle) {
            $searchQuery
                ->where('purchases.title', 'like', $like)
                ->orWhere('purchases.purchased_at', 'like', $like)
                ->orWhereHas('category', function ($categoryQuery) use ($like) {
                    $categoryQuery->where('description', 'like', $like);
                })
                ->orWhereHas('paymentMethod', function ($paymentMethodQuery) use ($like) {
                    $paymentMethodQuery->where('name', 'like', $like);
                })
                ->orWhereHas('creditCard', function ($creditCardQuery) use ($like) {
                    $creditCardQuery->where('title', 'like', $like);
                });

            if ($date !== null) {
                $searchQuery->orWhereDate('purchases.purchased_at', $date);
            }

            if ($formattedDate !== null) {
                $this->orWhereFormattedPurchasedAt($searchQuery, $formattedDate);
            }

            if ($partialDate !== null) {
                $searchQuery->orWhere(function ($dateQuery) use ($partialDate) {
                    $dateQuery->whereDay('purchases.purchased_at', $partialDate['day']);

                    if ($partialDate['month'] !== null) {
                        $dateQuery->whereMonth('purchases.purchased_at', $partialDate['month']);
                    }
                });
            }

            if ($amount !== null) {
                $searchQuery->orWhere('purchases.amount', 'like', '%' . $amount . '%');
            }

            if ($creditCardTitle !== null) {
                $searchQuery->orWhereHas('creditCard', function ($creditCardQuery) use ($creditCardTitle) {
                    $creditCardQuery->where('title', 'like', '%' . $creditCardTitle . '%');
                });
            }
        });
    }

    private function normalizeSearchDate(string $term): ?string
    {
        $formats = [
            'd/m/Y' => '/^\d{2}\/\d{2}\/\d{4}$/',
            'd-m-Y' => '/^\d{2}-\d{2}-\d{4}$/',
            'Y-m-d' => '/^\d{4}-\d{2}-\d{2}$/',
        ];

        foreach ($formats as $format => $pattern) {
            if (! preg_match($pattern, $term)) {
                continue;
            }

            $date = Carbon::createFromFormat('!' . $format, $term);

            if ($date !== false && $date->format($format) === $term) {
                return $date->toDateString();
            }
        }

        return null;
    }


    private function formattedDateSearch(string $term): ?string
    {
        return preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $term) ? $term : null;
    }


    private function partialDateSearch(string $term): ?array
    {
        if (preg_match('/^(\d{1,2})\/(\d{1,2})$/', $term, $matches)) {
            $day = (int) $matches[1];
            $month = (int) $matches[2];

            if ($day >= 1 && $day <= 31 && $month >= 1 && $month <= 12) {
                return ['day' => $day, 'month' => $month];
            }
        }

        if (preg_match('/^\d{1,2}$/', $term)) {
            $day = (int) $term;

            if ($day >= 1 && $day <= 31) {
                return ['day' => $day, 'month' => null];
            }
        }

        return null;
    }

    private function orWhereFormattedPurchasedAt($query, string $date): void
    {
        $driver = $query->getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $query->orWhereRaw("strftime('%d/%m/%Y', purchases.purchased_at) = ?", [$date]);

            return;
        }

        $query->orWhereRaw("DATE_FORMAT(purchases.purchased_at, '%d/%m/%Y') = ?", [$date]);
    }

    private function normalizeSearchAmount(string $term): ?string
    {
        $value = trim(str_replace(['R$', ' '], '', $term));

        if ($value === '') {
            return null;
        }

        if (str_contains($value, ',')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }

        if (! is_numeric($value)) {
            return null;
        }

        return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    }


    private function extractCreditCardTitleFromPaymentSearch(string $term): ?string
    {
        if (! preg_match('/cr[eé]dito\s*\(([^)]+)\)/iu', $term, $matches)) {
            return null;
        }

        $title = trim($matches[1]);

        return $title === '' ? null : $title;
    }
}
