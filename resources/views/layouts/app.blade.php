<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" class="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ config('app.name') }}@hasSection('title') - @yield('title')@endif</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-shell min-h-screen bg-background text-on-background antialiased">
    @auth
        @php
            $user = auth()->user();
            $isHr = $user->canViewSensitiveHr();
            $navLinks = [
                ['dashboard', 'dashboard', __('layout.nav.dashboard'), 'dashboard'],
                ['employees.*', 'employees.index', __('layout.nav.employees'), 'groups'],
                ['attendance.*', 'attendance.index', __('layout.nav.attendance'), 'timer'],
                ['leaves.*', 'leaves.index', __('layout.nav.leaves'), 'event_busy'],
            ];

            if ($isHr) {
                $navLinks = array_merge($navLinks, [
                    ['payroll.*', 'payroll.index', __('layout.nav.payroll'), 'payments'],
                    ['documents.*', 'documents.index', __('layout.nav.documents'), 'description'],
                    ['performance.*', 'performance.index', __('layout.nav.performance'), 'monitoring'],
                    ['reports.*', 'reports.index', __('layout.nav.reports'), 'bar_chart'],
                ]);
            }

            if ($user->can('manage-settings')) {
                $navLinks[] = ['devices.*', 'devices.index', __('layout.nav.devices'), 'fingerprint'];
                $navLinks[] = ['settings.*', 'dashboard', __('layout.nav.settings'), 'settings'];
            }
        @endphp

        <div class="flex min-h-screen overflow-hidden">
            <aside class="fixed inset-y-0 start-0 z-50 hidden w-sidebar-width flex-col bg-gradient-to-b from-[#1a2b4b] via-[#243b63] to-[#0f1d33] text-white shadow-[0_24px_56px_rgba(26,43,75,0.28)] lg:flex">
                <div class="flex flex-col gap-sm p-md">
                    <div class="flex items-center gap-3 py-sm">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-[#f4df96] via-[#d4af37] to-[#b68e17] text-[#1a2b4b] shadow-[0_14px_28px_rgba(212,175,55,0.28)]">
                            <span class="material-symbols-outlined fill">domain</span>
                        </div>
                        <div>
                            <h1 class="font-headline text-title-lg font-bold text-white">{{ __('layout.app_title') }}</h1>
                            <p class="font-label text-xs uppercase tracking-wide text-white/55">{{ __('layout.app_subtitle') }}</p>
                        </div>
                    </div>

                </div>

                <nav class="custom-scrollbar flex-1 space-y-1.5 overflow-y-auto px-sm py-md">
                    @foreach($navLinks as [$pattern, $route, $label, $icon])
                        <a href="{{ route($route) }}" class="flex items-center gap-3 rounded-lg border-s-4 px-4 py-3 transition-all active:scale-95 {{ request()->routeIs($pattern) ? 'border-[#d4af37] bg-white/12 font-bold text-white shadow-[inset_0_1px_0_rgba(255,255,255,0.08)]' : 'border-transparent text-white/70 hover:bg-white/10 hover:text-white' }}">
                            <span class="material-symbols-outlined {{ request()->routeIs($pattern) ? 'fill' : '' }}">{{ $icon }}</span>
                            <span class="text-body-md">{{ $label }}</span>
                        </a>
                    @endforeach
                </nav>

                <div class="mt-auto border-t border-white/10 p-sm">
                    <a class="flex items-center gap-3 rounded-lg px-4 py-3 text-white/68 transition-colors hover:bg-white/10 hover:text-white" href="#">
                        <span class="material-symbols-outlined">help</span>
                        <span class="text-body-md">{{ __('layout.help_center') }}</span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="flex w-full items-center gap-3 rounded-lg px-4 py-3 text-red-200 transition-colors hover:bg-red-500/15 hover:text-white">
                            <span class="material-symbols-outlined">logout</span>
                            <span class="text-body-md">{{ __('layout.logout') }}</span>
                        </button>
                    </form>
                </div>
            </aside>

            <main class="flex h-screen flex-1 flex-col overflow-hidden lg:ms-sidebar-width">
                <header class="sticky top-0 z-40 flex min-h-[72px] w-full items-center justify-between gap-4 border-b border-outline-variant/50 bg-white/88 px-md py-3 shadow-[0_10px_28px_rgba(25,28,30,0.05)] backdrop-blur-xl">
                    <div class="flex min-w-0 flex-[1.7] items-center justify-start">
                        <div class="relative hidden w-full max-w-3xl md:block xl:max-w-4xl">
                            <input class="h-11 w-full rounded-full border border-outline-variant/60 bg-surface-container-lowest py-2 ps-12 pe-5 text-body-sm text-on-surface shadow-[0_8px_22px_rgba(25,28,30,0.04)] transition placeholder:text-on-surface-variant/70 focus:border-secondary focus:bg-white focus:ring-4 focus:ring-secondary/10" placeholder="{{ __('layout.search_placeholder') }}" type="text">
                            <span class="material-symbols-outlined absolute start-4 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
                        </div>
                    </div>

                    <div class="flex flex-none items-center justify-end gap-3">
                        <div class="hidden items-center gap-2 sm:flex">
                            @if($isHr)
                                <a href="{{ route('nitaqat.calculator') }}" class="flex h-10 w-10 items-center justify-center rounded-full border border-outline-variant/50 bg-white text-on-surface shadow-sm transition hover:-translate-y-0.5 hover:border-secondary/40 hover:bg-primary-fixed hover:text-primary {{ request()->routeIs('nitaqat.calculator') ? 'border-secondary/50 bg-primary-fixed text-primary' : '' }}" title="{{ __('layout.nitaqat_calculator') }}" aria-label="{{ __('layout.nitaqat_calculator') }}">
                                    <span class="material-symbols-outlined text-[22px]">calculate</span>
                                </a>
                                <button class="flex h-10 w-10 items-center justify-center rounded-full border border-outline-variant/50 bg-white text-on-surface shadow-sm transition hover:-translate-y-0.5 hover:border-secondary/40 hover:bg-primary-fixed hover:text-primary" title="{{ __('layout.companies') }}">
                                    <span class="material-symbols-outlined text-[22px]">business_center</span>
                                </button>
                            @endif
                            @if(config('hr.sync.role') === 'branch')
                                @php
                                    $pendingSync = collect(\App\Services\Sync\SyncRegistry::MODELS)
                                        ->sum(fn ($model) => $model::query()->whereNull('synced_at')->count());
                                    $lastSyncedAt = \Illuminate\Support\Facades\DB::table('sync_log')->where('direction', 'push')->value('last_synced_at');
                                @endphp
                                <div class="flex h-10 items-center gap-2 rounded-full border px-3 text-xs font-bold {{ $pendingSync > 0 ? 'border-yellow-300 bg-yellow-50 text-yellow-800' : 'border-green-300 bg-green-50 text-green-800' }}"
                                     title="{{ $lastSyncedAt ? __('layout.sync.last_synced', ['time' => \Illuminate\Support\Carbon::parse($lastSyncedAt)->diffForHumans()]) : __('layout.sync.never_synced') }}">
                                    <span class="material-symbols-outlined text-[18px]">{{ $pendingSync > 0 ? 'sync_problem' : 'cloud_done' }}</span>
                                    <span>{{ $pendingSync > 0 ? __('layout.sync.pending', ['count' => $pendingSync]) : __('layout.sync.synced') }}</span>
                                </div>
                            @endif
                            @php $unreadNotifications = $user->unreadNotifications()->latest()->limit(10)->get(); @endphp
                            <details class="relative">
                                <summary class="relative flex h-10 w-10 cursor-pointer list-none items-center justify-center rounded-full border border-outline-variant/50 bg-white text-on-surface shadow-sm transition hover:-translate-y-0.5 hover:border-secondary/40 hover:bg-primary-fixed hover:text-primary" title="{{ __('layout.notifications') }}">
                                    <span class="material-symbols-outlined text-[22px]">notifications</span>
                                    @if($unreadNotifications->isNotEmpty())
                                        <span class="absolute end-2 top-2 flex h-4 min-w-4 items-center justify-center rounded-full border-2 border-white bg-error px-0.5 text-[9px] font-black text-white">{{ $user->unreadNotifications()->count() }}</span>
                                    @endif
                                </summary>
                                <div class="absolute end-0 top-12 z-50 w-96 max-w-[90vw] rounded-2xl border border-outline-variant/30 bg-white p-3 shadow-2xl">
                                    <div class="mb-2 flex items-center justify-between px-2">
                                        <h4 class="font-bold text-on-surface">{{ __('layout.notifications') }}</h4>
                                        @if($unreadNotifications->isNotEmpty())
                                            <form method="POST" action="{{ route('notifications.read-all') }}">
                                                @csrf
                                                <button class="text-xs font-bold text-primary hover:underline">{{ __('layout.notifications_panel.mark_all_read') }}</button>
                                            </form>
                                        @endif
                                    </div>
                                    @if($unreadNotifications->isEmpty())
                                        <p class="rounded-xl bg-surface-container-low p-4 text-sm text-on-surface-variant">{{ __('layout.notifications_panel.empty') }}</p>
                                    @else
                                        <ul class="max-h-80 space-y-1 overflow-y-auto">
                                            @foreach($unreadNotifications as $notification)
                                                <li class="rounded-xl p-3 text-sm hover:bg-surface-container-low">
                                                    @if(($notification->data['kind'] ?? null) === 'document_expiry')
                                                        <a href="{{ route('employees.show', $notification->data['employee_id']) }}" class="block">
                                                            <span class="font-bold text-on-surface">{{ $notification->data['message_ar'] }}</span>
                                                            <span class="mt-1 block text-xs text-on-surface-variant">{{ $notification->created_at->diffForHumans() }}</span>
                                                        </a>
                                                    @else
                                                        <span class="font-bold text-on-surface">{{ $notification->data['message_ar'] ?? __('layout.notifications_panel.generic') }}</span>
                                                        <span class="mt-1 block text-xs text-on-surface-variant">{{ $notification->created_at->diffForHumans() }}</span>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            </details>
                            <form method="POST" action="{{ route('locale.update') }}">
                                @csrf
                                <input type="hidden" name="locale" value="{{ app()->getLocale() === 'ar' ? 'en' : 'ar' }}">
                                <button type="submit" class="flex h-10 items-center gap-1.5 rounded-full border border-outline-variant/50 bg-white px-3 text-on-surface shadow-sm transition hover:-translate-y-0.5 hover:border-secondary/40 hover:bg-primary-fixed hover:text-primary" title="{{ __('layout.language') }}">
                                    <span class="material-symbols-outlined text-[22px]">language</span>
                                    <span class="text-xs font-bold uppercase">{{ app()->getLocale() === 'ar' ? 'EN' : 'AR' }}</span>
                                </button>
                            </form>
                        </div>

                        <div class="hidden items-center gap-3 rounded-full border border-outline-variant/50 bg-white py-1.5 ps-1.5 pe-4 shadow-[0_8px_22px_rgba(25,28,30,0.05)] transition hover:border-secondary/40 sm:flex">
                            <div class="flex h-11 w-11 items-center justify-center overflow-hidden rounded-full border-2 border-primary-container bg-primary-fixed text-sm font-black text-primary">
                                {{ mb_substr($user->name, 0, 1) }}
                            </div>
                            <div class="leading-tight">
                                <p class="text-title-sm font-bold text-on-surface">{{ $user->name }}</p>
                                <p class="mt-0.5 text-[11px] font-semibold text-on-surface-variant">{{ $isHr ? __('layout.role.hr_manager') : __('layout.role.user') }}</p>
                            </div>
                        </div>
                    </div>
                </header>

                <div class="hide-scrollbar flex-1 overflow-y-auto p-md pb-24 lg:p-lg">
                    @yield('content')
                </div>
            </main>

            <nav class="fixed bottom-0 start-0 end-0 z-50 grid grid-cols-4 border-t border-outline-variant bg-surface-container-lowest/95 py-2 shadow-[0_-8px_22px_rgba(25,28,30,0.10)] backdrop-blur-xl lg:hidden">
                <a href="{{ route('dashboard') }}" class="flex flex-col items-center {{ request()->routeIs('dashboard') ? 'font-bold text-primary' : 'text-on-surface-variant' }}"><span class="material-symbols-outlined {{ request()->routeIs('dashboard') ? 'fill' : '' }}">dashboard</span><span class="text-[10px]">{{ __('layout.nav.home') }}</span></a>
                <a href="{{ route('employees.index') }}" class="flex flex-col items-center {{ request()->routeIs('employees.*') ? 'font-bold text-primary' : 'text-on-surface-variant' }}"><span class="material-symbols-outlined {{ request()->routeIs('employees.*') ? 'fill' : '' }}">groups</span><span class="text-[10px]">{{ __('layout.nav.employees') }}</span></a>
                @if($isHr)
                    <a href="{{ route('payroll.index') }}" class="flex flex-col items-center {{ request()->routeIs('payroll.*') ? 'font-bold text-primary' : 'text-on-surface-variant' }}"><span class="material-symbols-outlined {{ request()->routeIs('payroll.*') ? 'fill' : '' }}">payments</span><span class="text-[10px]">{{ __('layout.nav.payroll') }}</span></a>
                @else
                    <a href="{{ route('leaves.index') }}" class="flex flex-col items-center {{ request()->routeIs('leaves.*') ? 'font-bold text-primary' : 'text-on-surface-variant' }}"><span class="material-symbols-outlined {{ request()->routeIs('leaves.*') ? 'fill' : '' }}">event_busy</span><span class="text-[10px]">{{ __('layout.nav.leaves') }}</span></a>
                @endif
                <a href="{{ route('attendance.index') }}" class="flex flex-col items-center {{ request()->routeIs('attendance.*') ? 'font-bold text-primary' : 'text-on-surface-variant' }}"><span class="material-symbols-outlined {{ request()->routeIs('attendance.*') ? 'fill' : '' }}">timer</span><span class="text-[10px]">{{ __('layout.nav.attendance') }}</span></a>
            </nav>
        </div>
    @else
        <main class="min-h-screen">
            @yield('content')
        </main>
    @endauth
</body>
</html>
