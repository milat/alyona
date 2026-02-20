<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'index')->name('home');

Route::redirect('/login', '/')->name('login');

Route::middleware('guest')->group(function () {
    Route::view('/register', 'auth.register')->name('register');
});

Route::middleware('auth')->group(function () {
    Route::view('/households/create', 'households.create')->name('households.create');
    Route::view('/households/invitations/create', 'households.invite')->name('households.invitations.create');
    Route::view('/households/period-settings', 'households.period-settings')->name('households.period-settings');
    Route::view('/categories', 'categories.index')->name('categories.index');
    Route::view('/categories/create', 'categories.create')->name('categories.create');
    Route::get('/categories/{category}/edit', function (App\Models\Category $category) {
        return view('categories.edit', compact('category'));
    })->name('categories.edit');
    Route::view('/purchases', 'purchases.index')->name('purchases.index');
    // Edicao de compras removida por requisicao
    Route::view('/incomes', 'incomes.index')->name('incomes.index');
    Route::get('/incomes/{income}/edit', function (App\Models\Income $income) {
        return view('incomes.edit', compact('income'));
    })->name('incomes.edit');
});
