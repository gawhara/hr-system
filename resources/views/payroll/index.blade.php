@extends('layouts.app')

@section('title', 'الرواتب')

@section('content')
    <div class="mx-auto max-w-7xl space-y-6">
        <section class="flex flex-col justify-between gap-4 rounded-3xl border border-outline-variant/50 bg-white p-6 shadow-[0_16px_38px_rgba(25,28,30,0.05)] md:flex-row md:items-end">
            <div>
                <p class="font-label text-xs font-bold uppercase tracking-[0.18em] text-secondary">Payroll</p>
                <h2 class="mt-2 text-3xl font-black text-on-surface">نظرة عامة على الرواتب</h2>
                <p class="mt-2 text-sm text-on-surface-variant">
                    الدورة الشهرية:
                    @if($currentCycle)
                        <span class="font-tabular">{{ $currentCycle->year }} / {{ str_pad($currentCycle->month, 2, '0', STR_PAD_LEFT) }}</span>
                        ({{ $currentCycle->period_starts_on?->format('Y-m-d') }} - {{ $currentCycle->period_ends_on?->format('Y-m-d') }})
                    @else
                        لا توجد دورة رواتب حالية
                    @endif
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <button class="flex items-center gap-2 rounded-xl bg-secondary-container px-5 py-3 font-bold text-on-secondary-container shadow-sm transition hover:opacity-90 active:scale-95">
                    <span class="material-symbols-outlined">download</span>
                    <span>تصدير ملف WPS</span>
                </button>
                <button class="flex items-center gap-2 rounded-xl bg-primary-container px-5 py-3 font-bold text-on-primary shadow-sm transition hover:opacity-90 active:scale-95">
                    <span class="material-symbols-outlined">play_arrow</span>
                    <span>تشغيل الرواتب</span>
                </button>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach([
                ['label' => 'صافي الرواتب', 'value' => number_format($summary['net'], 2).' ر.س', 'caption' => 'صافي مستحقات الموظفين', 'icon' => 'payments', 'tone' => 'bg-primary-fixed text-primary'],
                ['label' => 'إجمالي الرواتب', 'value' => number_format($summary['gross'], 2).' ر.س', 'caption' => 'قبل الاستقطاعات والبدلات', 'icon' => 'account_balance_wallet', 'tone' => 'bg-secondary-fixed text-secondary'],
                ['label' => 'إجمالي الاستقطاعات', 'value' => number_format($summary['deductions'] + $summary['gosi'], 2).' ر.س', 'caption' => 'تشمل التأمينات والاستقطاعات', 'icon' => 'trending_down', 'tone' => 'bg-error-container text-error'],
                ['label' => 'حالة ملف WPS', 'value' => $currentCycle ? 'جاهز للإرسال' : 'غير متاح', 'caption' => 'عدد الموظفين '.$summary['employees'], 'icon' => 'verified', 'tone' => 'bg-green-100 text-green-700'],
            ] as $metric)
                <div class="app-kpi-card p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-on-surface-variant">{{ $metric['label'] }}</p>
                            <strong class="mt-2 block truncate font-tabular text-2xl font-black text-on-surface">{{ $metric['value'] }}</strong>
                            <p class="mt-2 text-xs text-on-surface-variant">{{ $metric['caption'] }}</p>
                        </div>
                        <span class="material-symbols-outlined rounded-xl p-2 {{ $metric['tone'] }}">{{ $metric['icon'] }}</span>
                    </div>
                </div>
            @endforeach
        </section>

        <section class="app-card overflow-hidden">
            <div class="flex flex-col justify-between gap-3 border-b border-outline-variant/50 bg-white p-5 md:flex-row md:items-center">
                <h3 class="flex items-center gap-2 text-lg font-black text-on-surface">
                    <span class="material-symbols-outlined text-primary">list_alt</span>
                    قائمة الموظفين لهذا الشهر
                </h3>
                <span class="rounded-full bg-surface-container-low px-3 py-1 text-xs font-bold text-on-surface-variant">{{ number_format($summary['employees']) }} موظف</span>
            </div>
            <div class="overflow-x-auto">
                <table class="app-table min-w-[1060px] text-start text-sm">
                    <thead>
                        <tr>
                            <th class="px-6 py-4 font-bold">الموظف</th>
                            <th class="px-6 py-4 font-bold">المسمى الوظيفي</th>
                            <th class="px-6 py-4 text-end font-bold">الراتب الأساسي</th>
                            <th class="px-6 py-4 text-end font-bold">البدلات</th>
                            <th class="px-6 py-4 text-end font-bold">الاستقطاعات</th>
                            <th class="px-6 py-4 text-end font-bold">الصافي</th>
                            <th class="px-6 py-4 font-bold">الحالة</th>
                            <th class="px-6 py-4 text-center font-bold">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/35">
                        @forelse($items as $item)
                            @php($allowances = $item->housing_allowance + $item->transportation_allowance + $item->other_allowances)
                            <tr class="transition hover:bg-surface-container-low">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-fixed font-bold text-primary">{{ mb_substr($item->employee?->name_ar ?? '-', 0, 1) }}</div>
                                        <div>
                                            <div class="font-bold text-primary">{{ $item->employee?->name_ar }}</div>
                                            <div class="text-[11px] text-on-surface-variant">#{{ $item->employee?->employee_code }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">{{ $item->employee?->position?->title_ar ?? '-' }}</td>
                                <td class="px-6 py-4 text-end font-tabular">{{ number_format($item->basic_salary, 2) }}</td>
                                <td class="px-6 py-4 text-end font-tabular text-green-600">+{{ number_format($allowances, 2) }}</td>
                                <td class="px-6 py-4 text-end font-tabular text-error">-{{ number_format($item->total_deductions + $item->social_insurance_saudi, 2) }}</td>
                                <td class="px-6 py-4 text-end font-tabular font-black">{{ number_format($item->net_salary, 2) }}</td>
                                <td class="px-6 py-4"><span class="app-status-chip bg-green-100 text-green-700">جاهز</span></td>
                                <td class="px-6 py-4 text-center">
                                    <button class="rounded-full p-2 transition-colors hover:bg-surface-container"><span class="material-symbols-outlined text-primary">visibility</span></button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-6 py-12 text-center text-on-surface-variant">لا توجد بنود رواتب لهذه الدورة</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="app-card overflow-hidden">
            <div class="border-b border-outline-variant/50 bg-white p-5">
                <h3 class="flex items-center gap-2 text-lg font-black text-on-surface">
                    <span class="material-symbols-outlined text-primary">history</span>
                    دورات الرواتب
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="app-table min-w-[920px] text-start text-sm">
                    <thead>
                        <tr>
                            <th class="px-6 py-4 font-bold">الدورة</th>
                            <th class="px-6 py-4 font-bold">الشركة</th>
                            <th class="px-6 py-4 font-bold">عدد الموظفين</th>
                            <th class="px-6 py-4 font-bold">الإجمالي</th>
                            <th class="px-6 py-4 font-bold">الاستقطاعات</th>
                            <th class="px-6 py-4 font-bold">الصافي</th>
                            <th class="px-6 py-4 font-bold">الحالة</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/30">
                        @forelse($cycles as $cycle)
                            <tr class="transition hover:bg-surface-container-low">
                                <td class="px-6 py-4">
                                    <a href="{{ route('payroll.show', $cycle) }}" class="font-bold text-primary">{{ $cycle->year }} / {{ str_pad($cycle->month, 2, '0', STR_PAD_LEFT) }}</a>
                                </td>
                                <td class="px-6 py-4">{{ $cycle->company?->name_ar }}</td>
                                <td class="px-6 py-4 font-tabular">{{ $cycle->items_count }}</td>
                                <td class="px-6 py-4 font-tabular">{{ number_format($cycle->items_sum_gross_total ?? 0, 2) }}</td>
                                <td class="px-6 py-4 font-tabular">{{ number_format($cycle->items_sum_total_deductions ?? 0, 2) }}</td>
                                <td class="px-6 py-4 font-tabular font-black text-on-surface">{{ number_format($cycle->items_sum_net_salary ?? 0, 2) }}</td>
                                <td class="px-6 py-4"><span class="app-status-chip bg-surface-container text-primary">{{ $cycle->status }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-6 py-12 text-center text-on-surface-variant">لا توجد دورات رواتب</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-outline-variant/50 p-4">{{ $cycles->links() }}</div>
        </section>
    </div>
@endsection
