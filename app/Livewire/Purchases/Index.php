<?php

namespace App\Livewire\Purchases;

use App\Models\Category;
use App\Models\Purchase;
use App\Support\BudgetPeriod;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

class Index extends Component
{
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
        // Livewire re-renders the component after the month changes.
    }

    public function toggleSearch(): void
    {
        $this->showSearch = ! $this->showSearch;
    }

    public function toggleSort(): void
    {
        $this->showSort = ! $this->showSort;
    }

    public function applyFilters(): void
    {
        $this->selectedCategoryId = $this->categoryFilterInput ?: null;
        $this->search = trim($this->searchInput);
        $this->showSearch = false;
    }

    public function clearFilters(): void
    {
        $this->selectedCategoryId = null;
        $this->categoryFilterInput = null;
        $this->search = '';
        $this->searchInput = '';
        $this->showSearch = false;
    }

    public function applySort(): void
    {
        $allowedSorts = ['date', 'created_at', 'title', 'category', 'payment', 'amount'];
        $allowedDirections = ['asc', 'desc'];

        $this->sortBy = in_array($this->sortByInput, $allowedSorts, true) ? $this->sortByInput : 'date';
        $this->sortDirection = in_array($this->sortDirectionInput, $allowedDirections, true) ? $this->sortDirectionInput : 'desc';
        $this->showSort = false;
    }

    public function clearSort(): void
    {
        $this->sortBy = 'date';
        $this->sortDirection = 'desc';
        $this->sortByInput = 'date';
        $this->sortDirectionInput = 'desc';
        $this->showSort = false;
    }

    public function render()
    {
        $user = auth()->user();
        $purchases = collect();
        $monthOptions = collect();
        $categoryOptions = collect();
        $filteredTotal = 0;

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

                $primaryCategoryIds = Purchase::query()
                    ->where('household_id', $user->household_id)
                    ->whereBetween('reference_date', [
                        $period['start']->toDateString(),
                        $period['end']->toDateString(),
                    ])
                    ->distinct()
                    ->pluck('category_id');

                $allocationCategoryIds = \App\Models\PurchaseCategoryAllocation::query()
                    ->join('purchases', 'purchases.id', '=', 'purchase_category_allocations.purchase_id')
                    ->where('purchases.household_id', $user->household_id)
                    ->whereBetween('purchases.reference_date', [
                        $period['start']->toDateString(),
                        $period['end']->toDateString(),
                    ])
                    ->distinct()
                    ->pluck('purchase_category_allocations.category_id');

                $categoryIdsWithPurchases = $primaryCategoryIds->merge($allocationCategoryIds)->unique()->values();
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
                ->with(['category', 'categoryAllocations.category', 'paymentMethod', 'creditCard', 'user'])
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
                $selectedCategoryId = (int) $this->selectedCategoryId;
                $query->where(function ($categoryQuery) use ($selectedCategoryId) {
                    $categoryQuery->where('purchases.category_id', $selectedCategoryId)
                        ->orWhereHas('categoryAllocations', fn ($allocationQuery) => $allocationQuery->where('category_id', $selectedCategoryId));
                });
            }

            $this->applySearch($query);

            $filteredTotal = (float) (clone $query)->sum('purchases.amount');
            $this->applySorting($query);
            $purchases = $query->get();

            if ($purchases->isEmpty() && $this->hasActiveFilters()) {
                $this->showSearch = true;
            }
        }

        return view('livewire.purchases.index', [
            'purchases' => $purchases,
            'monthOptions' => $monthOptions,
            'categoryOptions' => $categoryOptions,
            'filteredTotal' => $filteredTotal,
        ]);
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
                ->orWhereHas('categoryAllocations.category', function ($categoryQuery) use ($like) {
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
