@extends('layouts.app')

@section('title', 'الرواتب')

@section('content')
    <div class="space-y-8">
        <div class="flex flex-col justify-between gap-md md:flex-row md:items-end">
            <div>
                <h2 class="text-display-lg font-bold text-primary">نظرة عامة على الرواتب</h2>
                <p class="mt-1 text-on-surface-variant">
                    الدورة الشهرية:
                    @if($currentCycle)
                        {{ $currentCycle->year }} / {{ str_pad($currentCycle->month, 2, '0', STR_PAD_LEFT) }}
                        ({{ $currentCycle->period_starts_on?->format('Y-m-d') }} - {{ $currentCycle->period_ends_on?->format('Y-m-d') }})
                    @else
                        لا توجد دورة رواتب حالية
                    @endif
                </p>
            </div>
            <div class="flex flex-wrap gap-sm">
                <button class="flex items-center gap-2 rounded-xl bg-secondary-container px-6 py-3 font-bold text-on-secondary-container shadow-sm transition-all hover:opacity-90 active:scale-95">
                    <span class="material-symbols-outlined">download</span>
                    <span>تصدير ملف WPS</span>
                </button>
                <button class="flex items-center gap-2 rounded-xl bg-primary-container px-6 py-3 font-bold text-on-primary shadow-sm transition-all hover:opacity-90 active:scale-95">
                    <span class="material-symbols-outlined">play_arrow</span>
                    <span>تشغيل الرواتب</span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-md md:grid-cols-2 xl:grid-cols-4">
            @foreach([
                ['صافي الرواتب', number_format($summary['net'], 2).' ر.س', '+4.2% مقارنة بالشهر السابق', 'payments', 'bg-primary-fixed text-primary', 'bg-primary'],
                ['إجمالي الرواتب', number_format($summary['gross'], 2).' ر.س', 'قبل الاستقطاعات والبدلات', 'account_balance_wallet', 'bg-secondary-fixed text-secondary', 'bg-secondary'],
                ['إجمالي الاستقطاعات', number_format($summary['deductions'] + $summary['gosi'], 2).' ر.س', 'تشمل التأمينات والاستقطاعات', 'trending_down', 'bg-error-container text-error', 'bg-error'],
                ['حالة ملف WPS', $currentCycle ? 'جاهز للإرسال' : 'غير متاح', 'SIF تم إنشاؤه لعدد '.$summary['employees'].' موظف', 'verified', 'bg-green-100 text-green-700', 'bg-green-500'],
            ] as [$label, $value, $caption, $icon, $tone, $rail])
                <div class="relative overflow-hidden rounded-xl border border-outline-variant bg-surface-container-lowest p-md shadow-sm">
                    <div class="absolute right-0 top-0 h-full w-2 {{ $rail }}"></div>
                    <div class="mb-sm flex items-start justify-between">
                        <span class="text-label-caps font-bold uppercase text-on-surface-variant">{{ $label }}</span>
                        <div class="rounded-lg p-2 {{ $tone }}"><span class="material-symbols-outlined">{{ $icon }}</span></div>
                    </div>
                    <div class="mb-1 text-headline-md font-bold text-primary">{{ $value }}</div>
                    <div class="flex items-center gap-1 text-body-sm text-on-surface-variant">{{ $caption }}</div>
                </div>
            @endforeach
        </div>

        <section class="overflow-hidden rounded-xl border border-outline-variant bg-surface-container-lowest shadow-sm">
            <div class="flex flex-col items-center justify-between gap-md border-b border-outline-variant bg-surface-container-low p-md md:flex-row">
                <h3 class="flex items-center gap-2 text-title-sm text-primary">
                    <span class="material-symbols-outlined">list_alt</span>
                    قائمة الموظفين لهذا الشهر
                </h3>
                <div class="flex w-full gap-2 md:w-auto">
                    <div class="relative flex-1 md:w-64">
                        <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-sm text-on-surface-variant">search</span>
                        <input class="w-full rounded-lg border border-outline-variant bg-white py-2 pl-3 pr-9 text-body-sm focus:border-primary focus:ring-primary" placeholder="بحث باسم الموظف..." type="text">
                    </div>
                    <button class="flex items-center gap-2 rounded-lg border border-outline-variant px-4 py-2 transition-colors hover:bg-surface-container">
                        <span class="material-symbols-outlined">filter_list</span>
                        <span class="text-body-sm">فلترة</span>
                    </button>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-right">
                    <thead>
                        <tr class="bg-surface-container-highest/30 text-on-surface-variant">
                            <th class="border-b border-outline-variant px-md py-4 font-bold">الموظف</th>
                            <th class="border-b border-outline-variant px-md py-4 font-bold">المسمى الوظيفي</th>
                            <th class="border-b border-outline-variant px-md py-4 text-left font-bold">الراتب الأساسي</th>
                            <th class="border-b border-outline-variant px-md py-4 text-left font-bold">البدلات</th>
                            <th class="border-b border-outline-variant px-md py-4 text-left font-bold">الاستقطاعات</th>
                            <th class="border-b border-outline-variant px-md py-4 text-left font-bold">الصافي</th>
                            <th class="border-b border-outline-variant px-md py-4 font-bold">الحالة</th>
                            <th class="border-b border-outline-variant px-md py-4 text-center font-bold">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/50 text-body-sm">
                        @forelse($items as $item)
                            @php($allowances = $item->housing_allowance + $item->transportation_allowance + $item->other_allowances)
                            <tr class="transition-colors hover:bg-surface-container-high/20">
                                <td class="px-md py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-fixed font-bold text-primary">{{ mb_substr($item->employee?->name_ar ?? '-', 0, 1) }}</div>
                                        <div>
                                            <div class="font-bold text-primary">{{ $item->employee?->name_ar }}</div>
                                            <div class="text-[11px] text-on-surface-variant">#{{ $item->employee?->employee_code }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-md py-4">{{ $item->employee?->position?->title_ar ?? '-' }}</td>
                                <td class="px-md py-4 text-left font-mono">{{ number_format($item->basic_salary, 2) }}</td>
                                <td class="px-md py-4 text-left font-mono text-green-600">+{{ number_format($allowances, 2) }}</td>
                                <td class="px-md py-4 text-left font-mono text-error">-{{ number_format($item->total_deductions + $item->social_insurance_saudi, 2) }}</td>
                                <td class="px-md py-4 text-left font-mono font-bold">{{ number_format($item->net_salary, 2) }}</td>
                                <td class="px-md py-4">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-1 text-[11px] font-bold text-green-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-green-700"></span> جاهز
                                    </span>
                                </td>
                                <td class="px-md py-4 text-center">
                                    <button class="rounded-full p-2 transition-colors hover:bg-surface-container"><span class="material-symbols-outlined text-primary">visibility</span></button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-6 py-10 text-center text-on-surface-variant">لا توجد بنود رواتب لهذه الدورة</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-outline-variant bg-surface-container-lowest shadow-sm">
            <div class="border-b border-outline-variant bg-surface-container-low p-md">
                <h3 class="flex items-center gap-2 text-title-sm font-bold text-primary">
                    <span class="material-symbols-outlined">history</span>
                    دورات الرواتب
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-right text-sm">
                    <thead class="bg-surface-container-highest/30 text-on-surface-variant">
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
                            <tr class="transition hover:bg-primary-container/5">
                                <td class="px-6 py-4">
                                    <a href="{{ route('payroll.show', $cycle) }}" class="font-bold text-primary">{{ $cycle->year }} / {{ str_pad($cycle->month, 2, '0', STR_PAD_LEFT) }}</a>
                                </td>
                                <td class="px-6 py-4">{{ $cycle->company?->name_ar }}</td>
                                <td class="px-6 py-4">{{ $cycle->items_count }}</td>
                                <td class="px-6 py-4">{{ number_format($cycle->items_sum_gross_total ?? 0, 2) }}</td>
                                <td class="px-6 py-4">{{ number_format($cycle->items_sum_total_deductions ?? 0, 2) }}</td>
                                <td class="px-6 py-4 font-black text-on-surface">{{ number_format($cycle->items_sum_net_salary ?? 0, 2) }}</td>
                                <td class="px-6 py-4"><span class="rounded-full bg-surface-container-highest px-3 py-1 text-xs font-bold text-primary">{{ $cycle->status }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-6 py-10 text-center text-on-surface-variant">لا توجد دورات رواتب</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-outline-variant p-4">{{ $cycles->links() }}</div>
        </section>
    </div>
@endsection
