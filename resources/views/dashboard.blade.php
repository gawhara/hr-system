@extends('layouts.app')

@section('title', 'لوحة التحكم')

@section('content')
    <div class="mx-auto grid max-w-7xl grid-cols-1 gap-6 md:grid-cols-2">
        @foreach($companyDashboards as $companyDashboard)
            @php
                $theme = $companyDashboard['theme'];
                $localizationPercent = $companyDashboard['localization_percent'];
                $localizationBand = $companyDashboard['nitaqat_band'];
                $payrollPerEmployee = $companyDashboard['headcount'] > 0
                    ? $companyDashboard['monthly_payroll'] / $companyDashboard['headcount']
                    : 0;
            @endphp

            <a href="{{ route('dashboard.companies.show', $companyDashboard['company']) }}" class="group min-h-[390px] overflow-hidden rounded-3xl bg-surface-container-lowest shadow-[0_22px_46px_rgba(17,24,39,0.07)] ring-1 ring-outline-variant/15 transition duration-200 hover:-translate-y-1 hover:shadow-[0_28px_54px_rgba(17,24,39,0.11)] focus:outline-none focus:ring-4" style="--tw-ring-color: {{ $theme['ring'] }};">
                <div class="h-2" style="background: linear-gradient(to left, {{ $theme['from'] }}, {{ $theme['to'] }});"></div>

                <div class="flex h-full flex-col gap-5 p-6">
                    <div class="flex min-h-24 items-center justify-between gap-5">
                        <div class="flex min-w-0 flex-1 flex-col justify-center">
                            <span class="mb-2 inline-flex w-fit items-center gap-1 rounded-full px-3 py-1 text-xs font-bold" style="background: {{ $theme['soft'] }}; color: {{ $theme['text'] }};">
                                <span class="material-symbols-outlined text-sm">dashboard</span>
                                لوحة شركة
                            </span>
                            <h2 class="font-headline text-2xl font-bold leading-8" style="color: {{ $theme['text'] }};">{{ $companyDashboard['company']->name_ar }}</h2>
                            <p class="mt-1 truncate text-xs font-semibold uppercase tracking-wide text-on-surface-variant">{{ $companyDashboard['company']->name }}</p>
                        </div>

                        <div class="flex h-24 w-28 shrink-0 items-center justify-center rounded-3xl bg-white p-4 shadow-[0_14px_30px_rgba(17,24,39,0.08)] ring-1" style="--tw-ring-color: {{ $theme['ring'] }};">
                            <img src="{{ asset($companyDashboard['logo']) }}" alt="{{ $companyDashboard['company']->name_ar }}" class="max-h-16 max-w-full object-contain">
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div class="rounded-2xl p-4" style="background: {{ $theme['soft'] }};">
                            <span class="block text-xs text-on-surface-variant">الموظفون</span>
                            <strong class="mt-2 block font-headline text-3xl font-bold" style="color: {{ $theme['text'] }};">{{ number_format($companyDashboard['headcount']) }}</strong>
                        </div>
                        <div class="rounded-2xl bg-surface-container-low p-4">
                            <span class="block text-xs text-on-surface-variant">السعوديون</span>
                            <strong class="mt-2 block font-headline text-3xl font-bold text-on-surface">{{ number_format($companyDashboard['saudis']) }}</strong>
                        </div>
                        <div class="rounded-2xl bg-surface-container-low p-4">
                            <span class="block text-xs text-on-surface-variant">غير السعوديين</span>
                            <strong class="mt-2 block font-headline text-3xl font-bold text-on-surface">{{ number_format($companyDashboard['non_saudis']) }}</strong>
                        </div>
                    </div>

                    <div class="rounded-2xl p-4" style="background: {{ $localizationBand['background'] }};">
                        <div class="mb-3 flex items-center justify-between gap-3 text-sm font-bold" style="color: {{ $localizationBand['text'] }};">
                            <span>نسبة التوطين</span>
                            <span class="rounded-full bg-white/80 px-3 py-1 text-xs">{{ $localizationBand['label'] }} - {{ $localizationPercent }}%</span>
                        </div>
                        <div class="h-2.5 rounded-full bg-white/75">
                            <div class="h-2.5 rounded-full" style="width: {{ $localizationPercent }}%; background: linear-gradient(to left, {{ $localizationBand['from'] }}, {{ $localizationBand['to'] }});"></div>
                        </div>
                    </div>

                    <div class="mt-auto grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-2xl border border-outline-variant/25 bg-white/60 p-4">
                            <span class="block text-on-surface-variant">صافي الرواتب</span>
                            <strong class="mt-2 block text-2xl font-bold" style="color: {{ $theme['text'] }};">{{ number_format($companyDashboard['monthly_payroll'], 0) }}</strong>
                        </div>
                        <div class="rounded-2xl border border-outline-variant/25 bg-white/60 p-4">
                            <span class="block text-on-surface-variant">متوسط الراتب</span>
                            <strong class="mt-2 block text-2xl font-bold" style="color: {{ $theme['text'] }};">{{ number_format($payrollPerEmployee, 0) }}</strong>
                        </div>
                        <div class="rounded-2xl border border-outline-variant/25 bg-white/60 p-4">
                            <span class="block text-on-surface-variant">إجازات معلقة</span>
                            <strong class="mt-2 block text-2xl font-bold" style="color: {{ $theme['text'] }};">{{ number_format($companyDashboard['pending_leaves']) }}</strong>
                        </div>
                        <div class="rounded-2xl border border-outline-variant/25 bg-white/60 p-4">
                            <span class="block text-on-surface-variant">تنبيهات وثائق</span>
                            <strong class="mt-2 block text-2xl font-bold" style="color: {{ $theme['text'] }};">{{ number_format($companyDashboard['document_alerts']) }}</strong>
                        </div>
                    </div>

                    <div class="flex items-center justify-between rounded-2xl px-4 py-3 text-white" style="background: {{ $theme['dark'] }};">
                        <span class="text-sm font-bold">فتح لوحة الشركة</span>
                        <span class="material-symbols-outlined transition group-hover:-translate-x-1">arrow_back</span>
                    </div>
                </div>
            </a>
        @endforeach
    </div>
@endsection
