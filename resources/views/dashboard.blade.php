@extends('layouts.app')

@section('title', 'لوحة التحكم')

@section('content')
    @php
        $totals = [
            'companies' => $companyDashboards->count(),
            'headcount' => $companyDashboards->sum('headcount'),
            'saudis' => $companyDashboards->sum('saudis'),
            'non_saudis' => $companyDashboards->sum('non_saudis'),
            'payroll' => $companyDashboards->sum('monthly_payroll'),
            'pending_leaves' => $companyDashboards->sum('pending_leaves'),
            'document_alerts' => $companyDashboards->sum('document_alerts'),
        ];

        $totals['localization_percent'] = $totals['headcount'] > 0
            ? round(($totals['saudis'] / $totals['headcount']) * 100, 1)
            : 0;

        $highestPayroll = max((float) $totals['payroll'], 1);
    @endphp

    <div class="mx-auto max-w-[1600px] space-y-4">
        <section class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            @forelse($companyDashboards as $companyDashboard)
                @php
                    $company = $companyDashboard['company'];
                    $localizationPercent = $companyDashboard['localization_percent'];
                    $localizationBand = $companyDashboard['nitaqat_band'];
                    $payrollPerEmployee = $companyDashboard['headcount'] > 0
                        ? $companyDashboard['monthly_payroll'] / $companyDashboard['headcount']
                        : 0;
                    $payrollWidth = min(100, round(($companyDashboard['monthly_payroll'] / $highestPayroll) * 100));
                    $saudiWidth = min(100, max(0, $localizationPercent));
                    $nonSaudiWidth = 100 - $saudiWidth;
                    $bandLabel = $localizationBand['label'] ?? 'نطاق غير محدد';
                @endphp

                <a href="{{ route('dashboard.companies.show', $company) }}"
                   class="group overflow-hidden rounded-2xl border border-outline-variant/50 bg-white shadow-[0_8px_20px_rgba(25,28,30,0.045)] transition hover:-translate-y-0.5 hover:shadow-[0_16px_32px_rgba(25,28,30,0.07)] focus:outline-none focus:ring-4 focus:ring-secondary/20">
                    <div class="border-b border-outline-variant/30 p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex min-w-0 items-center gap-3">
                                <div class="flex h-12 w-14 shrink-0 items-center justify-center rounded-xl border border-primary/10 bg-white p-1.5 shadow-sm">
                                    <img src="{{ asset($companyDashboard['logo']) }}" alt="{{ $company->name_ar }}" class="max-h-9 max-w-full object-contain">
                                </div>
                                <div class="min-w-0">
                                    <span class="mb-1 inline-flex rounded-full bg-primary-fixed px-2 py-0.5 text-[10px] font-black text-primary">لوحة شركة</span>
                                    <h2 class="truncate text-xl font-bold text-primary">{{ $company->name_ar }}</h2>
                                    <p class="truncate text-[11px] font-bold text-outline">{{ $company->name_en ?: $company->name_ar }}</p>
                                </div>
                            </div>

                            <div class="shrink-0 text-end">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-black ring-1 ring-black/5" style="background: {{ $localizationBand['background'] }}; color: {{ $localizationBand['text'] }};">
                                    {{ $bandLabel }}
                                </span>
                                <p class="mt-1 text-xs font-bold text-primary">توطين: <span class="font-tabular">{{ $localizationPercent }}%</span></p>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-4 p-4 md:grid-cols-[1fr_0.9fr]">
                        <div class="space-y-3">
                            <div>
                                <p class="text-[11px] font-bold text-outline">إجمالي الموظفين</p>
                                <div class="mt-1 flex items-end gap-2">
                                    <span class="font-tabular text-3xl font-black leading-none text-primary">{{ number_format($companyDashboard['headcount']) }}</span>
                                    <span class="mb-0.5 inline-flex items-center gap-1 text-[11px] font-black text-secondary">
                                        <span class="material-symbols-outlined text-sm">trending_up</span>
                                        {{ $payrollWidth }}%
                                    </span>
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <div class="flex justify-between gap-3 text-xs text-on-surface-variant">
                                    <span>سعودي ({{ number_format($companyDashboard['saudis']) }})</span>
                                    <span>أجنبي ({{ number_format($companyDashboard['non_saudis']) }})</span>
                                </div>
                                <div class="flex h-2 w-full overflow-hidden rounded-full bg-surface-container-high">
                                    <div class="h-full bg-secondary" style="width: {{ $saudiWidth }}%"></div>
                                    <div class="h-full bg-primary/20" style="width: {{ $nonSaudiWidth }}%"></div>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl bg-surface-container-low p-3">
                            <div class="space-y-2">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-[11px] font-bold text-outline">صافي الرواتب</span>
                                    <strong class="font-tabular text-sm font-black text-primary">SAR {{ number_format($companyDashboard['monthly_payroll'], 0) }}</strong>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-[11px] font-bold text-outline">متوسط الراتب</span>
                                    <strong class="font-tabular text-sm font-semibold text-on-surface-variant">SAR {{ number_format($payrollPerEmployee, 0) }}</strong>
                                </div>
                                <div class="border-t border-outline-variant/60 pt-2">
                                    <div class="flex flex-wrap gap-x-4 gap-y-1">
                                        <span class="inline-flex items-center gap-1 text-xs font-black text-error">
                                            <span class="material-symbols-outlined text-sm">event_busy</span>
                                            {{ number_format($companyDashboard['pending_leaves']) }} إجازة
                                        </span>
                                        <span class="inline-flex items-center gap-1 text-xs font-black text-tertiary-container">
                                            <span class="material-symbols-outlined text-sm">warning</span>
                                            {{ number_format($companyDashboard['document_alerts']) }} تنبيه
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-center gap-2 bg-primary px-4 py-2.5 text-sm font-bold text-white transition group-hover:bg-primary-container">
                        <span>فتح لوحة تحكم الشركة</span>
                        <span class="material-symbols-outlined text-lg transition group-hover:-translate-x-1">chevron_left</span>
                    </div>
                </a>
            @empty
                <div class="rounded-2xl border border-outline-variant/50 bg-white p-8 text-center text-on-surface-variant xl:col-span-2">
                    لا توجد شركات متاحة للعرض.
                </div>
            @endforelse
        </section>

        <section>
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-xl font-bold text-primary">رؤى المجموعة الشاملة</h3>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="flex items-center gap-3 rounded-2xl border border-outline-variant/50 bg-white p-4 shadow-[0_8px_18px_rgba(25,28,30,0.04)]">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-tertiary-fixed text-on-tertiary-fixed">
                        <span class="material-symbols-outlined text-2xl">trending_up</span>
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-[11px] font-bold text-outline">متوسط التوطين العام</p>
                        <p class="mt-0.5 font-tabular text-2xl font-black leading-none text-primary">{{ $totals['localization_percent'] }}%</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 rounded-2xl border border-outline-variant/50 bg-white p-4 shadow-[0_8px_18px_rgba(25,28,30,0.04)]">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-secondary-fixed text-on-secondary-fixed">
                        <span class="material-symbols-outlined text-2xl">payments</span>
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-[11px] font-bold text-outline">إجمالي الكتلة النقدية</p>
                        <p class="mt-0.5 truncate font-tabular text-2xl font-black leading-none text-primary">SAR {{ number_format($totals['payroll'], 0) }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 rounded-2xl border border-outline-variant/50 bg-white p-4 shadow-[0_8px_18px_rgba(25,28,30,0.04)]">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-error-container text-on-error-container">
                        <span class="material-symbols-outlined text-2xl">alarm</span>
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-[11px] font-bold text-outline">وثائق منتهية أو قريبة الانتهاء</p>
                        <p class="mt-0.5 font-tabular text-2xl font-black leading-none text-primary">{{ number_format($totals['document_alerts']) }} وثيقة</p>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
