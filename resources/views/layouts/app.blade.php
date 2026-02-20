<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Alyona') }} | @yield('title', 'Home')</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/favicon/favicon.ico') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    {{-- Web App --}}
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Alyona">
    <link rel="apple-touch-icon" href="{{ asset('images/favicon/apple-touch-icon.png') }}">
    <meta name="theme-color" content="#0d6efd">
    <meta name="mobile-web-app-capable" content="yes">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/favicon/android-chrome-192x192.png') }}">
    <link rel="icon" type="image/png" sizes="512x512" href="{{ asset('images/favicon/android-chrome-512x512.png') }}">
    @livewireStyles
</head>
<body class="d-flex flex-column min-vh-100">
<header>
    @include('layouts.navbar')
</header>

@if($errors->any())
    <div class="alert alert-danger alert-auto-dismiss text-center">
        <b>Erro:</b><br />
        @foreach($errors->all() as $error)
            - {{ $error }}<br />
        @endforeach
    </div>
@endif

@if (session('success'))
    <div class="alert alert-success alert-auto-dismiss text-center">
        {!! nl2br(e(session('success'))) !!}
    </div>
@endif

<main class="flex-grow-1">
    @yield('content')
</main>

<footer class="bg-light text-center py-0 mt-auto">
    <p class="mb-0 small">&copy; {{ date('Y') }} milat.dev</p>
</footer>

<script>
    document.querySelectorAll('.alert.alert-auto-dismiss').forEach((alert) => {
        alert.style.transition = 'opacity 0.8s ease';
        setTimeout(() => {
            alert.style.opacity = '0';
        }, 5000);
        setTimeout(() => {
            alert.remove();
        }, 5800);
    });
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('{{ asset('sw.js') }}');
    }
</script>
<script>
    document.addEventListener('livewire:init', () => {
        const cleanupModalBackdrop = () => {
            const hasOpenModal = document.querySelector('.modal.show') !== null;
            if (hasOpenModal) return;

            document.querySelectorAll('.modal-backdrop').forEach((el) => el.remove());
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('overflow');
            document.body.style.removeProperty('padding-right');
        };

        document.addEventListener('hidden.bs.modal', cleanupModalBackdrop);

        Livewire.on('purchase-saved', () => {
            const modalEl = document.getElementById('purchaseModal');
            if (! modalEl) return;
            const instance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            instance.hide();

            // Re-render dashboard chart after Livewire updates dashboard HTML.
            setTimeout(() => {
                if (typeof window.renderDashboardPeriodChart === 'function') {
                    window.renderDashboardPeriodChart();
                }
            }, 120);
        });

        Livewire.on('income-saved', () => {
            const modalEl = document.getElementById('incomeModal');
            if (! modalEl) return;
            const instance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            instance.hide();
        });

        document.addEventListener('livewire:navigated', cleanupModalBackdrop);
    });
</script>
@livewireScripts
@livewireScriptConfig
</body>
</html>
