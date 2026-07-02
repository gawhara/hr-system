@extends('layouts.app')

@section('title', 'لوحة التحكم')

@section('content')
    <div class="mx-auto grid max-w-7xl grid-cols-1 gap-5 md:grid-cols-2">
        @foreach($companyDashboards as $index => $companyDashboard)
            @php
                $company = $companyDashboard['company'];
                $localizationPercent = $companyDashboard['localization_percent'];
                $localizationBand = $companyDashboard['nitaqat_band'];
                $payrollPerEmployee = $companyDashboard['headcount'] > 0
                    ? $companyDashboard['monthly_payroll'] / $companyDashboard['headcount']
                    : 0;
                $cardTone = [
                    ['from' => '#2e1065', 'via' => '#6d28d9', 'to' => '#d946ef'],
                    ['from' => '#3b0764', 'via' => '#7c3aed', 'to' => '#a21caf'],
                    ['from' => '#1e1b4b', 'via' => '#6d28d9', 'to' => '#c026d3'],
                    ['from' => '#581c87', 'via' => '#8b5cf6', 'to' => '#db2777'],
                ][$index % 4];
                $quickStats = [
                    ['الموظفون', number_format($companyDashboard['headcount']), 'groups'],
                    ['السعوديون', number_format($companyDashboard['saudis']), 'verified_user'],
                    ['غير السعوديين', number_format($companyDashboard['non_saudis']), 'public'],
                ];
                $detailStats = [
                    ['صافي الرواتب', number_format($companyDashboard['monthly_payroll'], 0), 'payments'],
                    ['متوسط الراتب', number_format($payrollPerEmployee, 0), 'trending_up'],
                    ['إجازات معلقة', number_format($companyDashboard['pending_leaves']), 'event_busy'],
                    ['تنبيهات وثائق', number_format($companyDashboard['document_alerts']), 'notification_important'],
                ];
            @endphp

            <a href="{{ route('dashboard.companies.show', $company) }}"
               class="group relative isolate min-h-[440px] overflow-hidden rounded-3xl border border-white/70 bg-white/82 p-1 shadow-[0_24px_54px_rgba(88,28,135,0.14)] ring-1 ring-outline-variant/20 backdrop-blur-xl transition duration-200 hover:-translate-y-1 hover:shadow-[0_30px_64px_rgba(88,28,135,0.20)] focus:outline-none focus:ring-4 focus:ring-primary/20">
                <div class="absolute inset-0 -z-10 opacity-0 transition duration-300 group-hover:opacity-100"
                     style="background: radial-gradient(circle at 18% 14%, {{ $cardTone['to'] }}24, transparent 28%), radial-gradient(circle at 86% 8%, {{ $cardTone['via'] }}20, transparent 30%);"></div>

                <div class="flex h-full flex-col overflow-hidden rounded-[1.35rem] bg-white/84">
                    <div class="relative overflow-hidden p-5 text-white"
                         style="background: linear-gradient(135deg, {{ $cardTone['from'] }}, {{ $cardTone['via'] }} 54%, {{ $cardTone['to'] }});">
                        <div class="absolute -left-12 -top-16 h-44 w-44 rounded-full bg-white/12 blur-2xl"></div>
                        <div class="absolute -bottom-20 right-16 h-48 w-48 rounded-full bg-fuchsia-200/15 blur-3xl"></div>

                        <div class="relative flex items-start justify-between gap-5">
                            <div class="min-w-0 flex-1">
                                <span class="inline-flex items-center gap-2 rounded-full border border-white/14 bg-white/12 px-3 py-1 text-xs font-bold text-white/86 backdrop-blur-md">
                                    <span class="material-symbols-outlined text-sm">dashboard</span>
                                    لوحة شركة
                                </span>
                                <h2 class="mt-4 font-headline text-2xl font-black leading-8 text-white">{{ $company->name_ar }}</h2>
                                <p class="mt-1 truncate text-xs font-bold uppercase tracking-wide text-white/58">{{ $company->name }}</p>
                            </div>

                            <div class="flex h-24 w-28 shrink-0 items-center justify-center rounded-3xl border border-white/30 bg-white p-4 shadow-[0_18px_36px_rgba(30,16,56,0.22)]">
                                <img src="{{ asset($companyDashboard['logo']) }}" alt="{{ $company->name_ar }}" class="max-h-16 max-w-full object-contain">
                            </div>
                        </div>

                        <div class="relative mt-5 grid grid-cols-3 gap-3">
                            @foreach($quickStats as [$label, $value, $icon])
                                <div class="rounded-2xl border border-white/12 bg-white/12 p-3 backdrop-blur-md">
                                    <div class="flex items-center justify-between gap-2 text-white/70">
                                        <span class="text-xs font-bold">{{ $label }}</span>
                                        <span class="material-symbols-outlined text-base">{{ $icon }}</span>
                                    </div>
                                    <strong class="mt-2 block font-headline text-2xl font-black text-white">{{ $value }}</strong>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex flex-1 flex-col gap-4 p-5">
                        <div class="rounded-3xl border border-outline-variant/30 bg-surface-container-lowest/78 p-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.75)]">
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <div>
                                    <span class="block text-xs font-bold text-on-surface-variant">نسبة التوطين</span>
                                    <strong class="mt-1 block font-headline text-3xl text-on-surface">{{ $localizationPercent }}%</strong>
                                </div>
                                <span class="rounded-full px-3 py-1 text-xs font-black ring-1" style="background: {{ $localizationBand['background'] }}; color: {{ $localizationBand['text'] }}; --tw-ring-color: {{ $localizationBand['text'] }}22;">
                                    {{ $localizationBand['label'] }}
                                </span>
                            </div>
                            <div class="h-3 overflow-hidden rounded-full bg-surface-container">
                                <div class="h-full rounded-full transition-all duration-500" style="width: {{ $localizationPercent }}%; background: linear-gradient(to left, {{ $localizationBand['from'] }}, {{ $localizationBand['to'] }});"></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            @foreach($detailStats as [$label, $value, $icon])
                                <div class="rounded-2xl border border-outline-variant/25 bg-white/72 p-4 shadow-[0_12px_24px_rgba(88,28,135,0.06)]">
                                    <div class="mb-3 flex items-center justify-between gap-2">
                                        <span class="text-xs font-bold text-on-surface-variant">{{ $label }}</span>
                                        <span class="material-symbols-outlined text-lg text-primary">{{ $icon }}</span>
                                    </div>
                                    <strong class="block truncate text-2xl font-black text-on-surface">{{ $value }}</strong>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-auto flex items-center justify-between rounded-2xl bg-primary px-4 py-3 text-white shadow-[0_16px_30px_rgba(109,40,217,0.22)]">
                            <span class="text-sm font-black">فتح لوحة الشركة</span>
                            <span class="material-symbols-outlined transition group-hover:-translate-x-1">arrow_back</span>
                        </div>
                    </div>
                </div>
            </a>
        @endforeach
    </div>
@endsection
