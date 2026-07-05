@extends('layouts.app')

@section('title', $company->name_ar)

@section('content')
    @php
        $theme = $dashboard['theme'];
        $headcount = max((int) $dashboard['headcount'], 1);
        $localizationPercent = (int) $localizationPercent;
        $nonSaudiPercent = round(((int) $dashboard['non_saudis'] / $headcount) * 100);
        $malePercent = round(((int) $genderChart['male'] / $headcount) * 100);
        $femalePercent = round(((int) $genderChart['female'] / $headcount) * 100);
        $nitaqatBand = $dashboard['nitaqat_band'];
        $payrollPerEmployee = $dashboard['headcount'] > 0 ? $dashboard['monthly_payroll'] / $dashboard['headcount'] : 0;
    @endphp

    <div class="mx-auto max-w-[1600px] space-y-4">
        <section class="overflow-hidden rounded-2xl border border-outline-variant/50 bg-white shadow-[0_10px_28px_rgba(25,28,30,0.05)]">
            <div class="h-1.5" style="background: linear-gradient(to left, {{ $theme['from'] }}, {{ $theme['to'] }});"></div>
            <div class="flex flex-col gap-4 p-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex min-w-0 items-center gap-4">
                    <div class="flex h-16 w-20 shrink-0 items-center justify-center rounded-2xl border border-primary/10 bg-white p-2 shadow-sm">
                        <img src="{{ asset($dashboard['logo']) }}" alt="{{ $company->name_ar }}" class="max-h-12 max-w-full object-contain">
                    </div>
                    <div class="min-w-0">
                        <a href="{{ route('dashboard') }}" class="mb-1 inline-flex items-center gap-1 text-xs font-black text-primary hover:underline">
                            <span class="material-symbols-outlined text-sm">arrow_forward</span>
                            العودة للوحة الرئيسية
                        </a>
                        <h1 class="truncate text-2xl font-black text-primary">{{ $company->name_ar }}</h1>
                        <p class="truncate text-xs font-bold uppercase tracking-wide text-outline">{{ $company->name_en ?: $company->name_ar }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:min-w-[520px]">
                    @foreach([
                        ['label' => 'الموظفون', 'value' => $dashboard['headcount'], 'icon' => 'groups'],
                        ['label' => 'النشطون', 'value' => $activeEmployees, 'icon' => 'verified_user'],
                        ['label' => 'الفروع', 'value' => $branchesCount, 'icon' => 'account_tree'],
                        ['label' => 'الأقسام', 'value' => $departmentsCount, 'icon' => 'category'],
                    ] as $metric)
                        <div class="rounded-2xl bg-surface-container-low px-3 py-2.5">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[11px] font-bold text-on-surface-variant">{{ $metric['label'] }}</span>
                                <span class="material-symbols-outlined text-base text-secondary">{{ $metric['icon'] }}</span>
                            </div>
                            <strong class="mt-1 block font-tabular text-2xl font-black leading-none text-primary">{{ number_format($metric['value']) }}</strong>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-4 xl:grid-cols-[1.1fr_0.9fr]">
            <div class="rounded-2xl border border-outline-variant/50 bg-white p-4 shadow-[0_8px_20px_rgba(25,28,30,0.04)]">
                <div class="flex flex-col gap-4 md:flex-row md:items-center">
                    <div class="grid shrink-0 place-items-center">
                        <div class="grid h-36 w-36 place-items-center rounded-full" style="background: conic-gradient({{ $nitaqatBand['from'] }} 0 {{ $localizationPercent }}%, #e6e8ea {{ $localizationPercent }}% 100%);">
                            <div class="grid h-24 w-24 place-items-center rounded-full bg-white text-center shadow-sm">
                                <span class="text-[11px] font-bold text-on-surface-variant">التوطين</span>
                                <strong class="font-tabular text-3xl font-black leading-none" style="color: {{ $nitaqatBand['text'] }};">{{ $localizationPercent }}%</strong>
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-black" style="background: {{ $nitaqatBand['background'] }}; color: {{ $nitaqatBand['text'] }};">{{ $nitaqatBand['label'] }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid flex-1 gap-3 md:grid-cols-2">
                        <div class="rounded-2xl bg-surface-container-low p-3">
                            <div class="mb-1.5 flex justify-between text-xs font-bold">
                                <span>السعوديون</span>
                                <span>{{ number_format($dashboard['saudis']) }}</span>
                            </div>
                            <div class="h-2 rounded-full bg-surface-container-high">
                                <div class="h-2 rounded-full" style="width: {{ $localizationPercent }}%; background: linear-gradient(to left, {{ $nitaqatBand['from'] }}, {{ $nitaqatBand['to'] }});"></div>
                            </div>
                        </div>
                        <div class="rounded-2xl bg-surface-container-low p-3">
                            <div class="mb-1.5 flex justify-between text-xs font-bold">
                                <span>غير السعوديين</span>
                                <span>{{ number_format($dashboard['non_saudis']) }}</span>
                            </div>
                            <div class="h-2 rounded-full bg-surface-container-high">
                                <div class="h-2 rounded-full" style="width: {{ $nonSaudiPercent }}%; background: {{ $theme['muted'] }};"></div>
                            </div>
                        </div>
                        <div class="rounded-2xl bg-surface-container-low p-3">
                            <p class="text-[11px] font-bold text-outline">الذكور</p>
                            <strong class="mt-1 block font-tabular text-xl font-black text-primary">{{ number_format($genderChart['male']) }}</strong>
                            <div class="mt-2 h-2 rounded-full bg-surface-container-high">
                                <div class="h-2 rounded-full" style="width: {{ $malePercent }}%; background: {{ $theme['from'] }};"></div>
                            </div>
                        </div>
                        <div class="rounded-2xl bg-surface-container-low p-3">
                            <p class="text-[11px] font-bold text-outline">الإناث</p>
                            <strong class="mt-1 block font-tabular text-xl font-black text-primary">{{ number_format($genderChart['female']) }}</strong>
                            <div class="mt-2 h-2 rounded-full bg-surface-container-high">
                                <div class="h-2 rounded-full" style="width: {{ $femalePercent }}%; background: {{ $theme['to'] }};"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                @foreach([
                    ['label' => 'صافي الرواتب', 'value' => 'SAR '.number_format($dashboard['monthly_payroll'], 0), 'icon' => 'payments', 'tone' => 'bg-secondary-fixed text-secondary'],
                    ['label' => 'متوسط الراتب', 'value' => 'SAR '.number_format($payrollPerEmployee, 0), 'icon' => 'trending_up', 'tone' => 'bg-primary-fixed text-primary'],
                    ['label' => 'إجازات معلقة', 'value' => number_format($dashboard['pending_leaves']), 'icon' => 'event_busy', 'tone' => 'bg-error-container text-error'],
                    ['label' => 'تنبيهات وثائق', 'value' => number_format($dashboard['document_alerts']), 'icon' => 'warning', 'tone' => 'bg-yellow-100 text-yellow-800'],
                ] as $metric)
                    <div class="rounded-2xl border border-outline-variant/50 bg-white p-4 shadow-[0_8px_20px_rgba(25,28,30,0.04)]">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-[11px] font-bold text-outline">{{ $metric['label'] }}</p>
                                <strong class="mt-2 block truncate font-tabular text-2xl font-black leading-none text-primary">{{ $metric['value'] }}</strong>
                            </div>
                            <span class="material-symbols-outlined rounded-xl p-2 {{ $metric['tone'] }}">{{ $metric['icon'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            <div class="rounded-2xl border border-outline-variant/50 bg-white p-4 shadow-[0_8px_20px_rgba(25,28,30,0.04)]">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-black text-primary">الأقسام الأعلى عددا</h2>
                    <span class="material-symbols-outlined text-secondary">bar_chart</span>
                </div>
                <div class="space-y-3">
                    @forelse($departmentChart as $department)
                        <div>
                            <div class="mb-1.5 flex justify-between text-xs font-bold">
                                <span>{{ $department['name'] }}</span>
                                <span class="font-tabular">{{ number_format($department['total']) }}</span>
                            </div>
                            <div class="h-2.5 rounded-full bg-surface-container">
                                <div class="h-2.5 rounded-full" style="width: {{ $department['percent'] }}%; background: linear-gradient(to left, {{ $theme['from'] }}, {{ $theme['to'] }});"></div>
                            </div>
                        </div>
                    @empty
                        <p class="rounded-2xl bg-surface-container-low p-4 text-center text-sm text-on-surface-variant">لا توجد بيانات أقسام لهذه الشركة.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-2xl border border-outline-variant/50 bg-white p-4 shadow-[0_8px_20px_rgba(25,28,30,0.04)]">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-black text-primary">ملخص الشركة</h2>
                    <span class="material-symbols-outlined text-secondary">insights</span>
                </div>
                <div class="grid gap-3 md:grid-cols-2">
                    <div class="rounded-2xl bg-surface-container-low p-4">
                        <p class="text-[11px] font-bold text-outline">نسبة غير السعوديين</p>
                        <strong class="mt-1 block font-tabular text-2xl font-black text-primary">{{ $nonSaudiPercent }}%</strong>
                    </div>
                    <div class="rounded-2xl bg-surface-container-low p-4">
                        <p class="text-[11px] font-bold text-outline">متوسط الراتب الأساسي</p>
                        <strong class="mt-1 block font-tabular text-2xl font-black text-primary">SAR {{ number_format($averageSalary, 0) }}</strong>
                    </div>
                    <div class="rounded-2xl p-4 text-white md:col-span-2" style="background: {{ $theme['dark'] }};">
                        <p class="text-xs font-bold text-white/65">حالة الصفحة</p>
                        <strong class="mt-1 block text-base font-black">لوحة تفصيلية خاصة بالشركة</strong>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
