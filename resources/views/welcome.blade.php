<!DOCTYPE html>
<html lang="en-IN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Dhansree — PHP port</title>
    {{-- The stylesheet is compiled by scripts/build-css.sh (standalone Tailwind CLI). There is
         deliberately no @vite() here: this application has no Node toolchain. --}}
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="min-h-screen bg-white text-slate-900 antialiased">
    <main class="mx-auto max-w-2xl px-4 py-16">
        <h1 class="text-2xl font-semibold tracking-tight">Dhansree — Laravel port</h1>
        <p class="mt-3 text-slate-600">
            Foundation only. No storefront, checkout, orders, payments, shipping or admin features
            are ported yet.
        </p>
        <dl class="mt-8 space-y-2 text-sm">
            <div class="flex gap-2">
                <dt class="w-40 text-slate-500">Laravel</dt>
                <dd>{{ app()->version() }}</dd>
            </div>
            <div class="flex gap-2">
                <dt class="w-40 text-slate-500">PHP</dt>
                <dd>{{ PHP_VERSION }}</dd>
            </div>
            <div class="flex gap-2">
                <dt class="w-40 text-slate-500">Asset pipeline</dt>
                <dd>Standalone Tailwind CLI &mdash; no Node</dd>
            </div>
        </dl>
        <p class="mt-8 text-sm text-slate-500">
            See <code class="rounded bg-slate-100 px-1 py-0.5">docs/MIGRATION_PLAN.md</code> for the plan.
        </p>
    </main>
</body>
</html>
