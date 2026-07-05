@extends('layouts.app')

@section('title', 'تفاصيل الرواتب')

@section('content')
    @php
        $grossTotal = $cycle->items->sum('gross_total');
        $deductionsTotal = $cycle->items->sum('total_deductions');
        $gosiTotal = $cycle->items->sum('social_insurance_saudi');
        $netTotal = $cycle->items->sum('net_salary');
        $itemsCount = $cycle->items->count();
        $statusMeta = [
            'draft' => ['label' => 'مسودة', 'class' => 'bg-surface-container text-on-surface-variant', 'icon' => 'edit_note'],
            'under_review' => ['label' => 'قيد المراجعة', 'class' => 'bg-yellow-100 text-yellow-800', 'icon' => 'rate_review'],
            'approved' => ['label' => 'معتمد', 'class' => 'bg-blue-100 text-blue-800', 'icon' => 'verified'],
            'locked' => ['label' => 'مقفل', 'class' => 'bg-green-100 text-green-800', 'icon' => 'lock'],
        ][$cycle->status] ?? ['label' => $cycle->status, 'class' => 'bg-surface-container text-on-surface-variant', 'icon' => 'info'];
    @endphp

    <div class="mx-auto max-w-7xl space-y-6">
        @if(session('status'))
            <div class="rounded-2xl border border-green-200 bg-green-50 p-4 text-sm font-bold text-green-800">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-bold text-red-800">{{ $errors->first() }}</div>
        @endif

        <section class="overflow-hidden rounded-3xl bg-primary text-white shadow-[0_20px_50px_rgba(61,32,143,0.18)]">
            <div class="flex flex-col gap-6 p-6 md:flex-row md:items-start md:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2 text-xs font-bold text-white/70">
                        <span class="font-label uppercase tracking-[0.18em]">Payroll Cycle</span>
                        @if($cycle->parent_cycle_id)
                            <span>&middot;</span>
                            <a href="{{ route('payroll.show', $cycle->parent_cycle_id) }}" class="text-white underline decoration-white/40 underline-offset-4 hover:decoration-white">
                                مسير تسوية للمسير رقم {{ $cycle->parent_cycle_id }}
                            </a>
                        @endif
                    </div>
                    <h2 class="mt-3 text-3xl font-black leading-tight md:text-4xl">
                        {{ $cycle->company?->name_ar }} - {{ $cycle->year }} / {{ str_pad($cycle->month, 2, '0', STR_PAD_LEFT) }}
                    </h2>
                    <div class="mt-4 flex flex-wrap items-center gap-3 text-sm text-white/75">
                        <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 ring-1 ring-white/15">
                            <span class="material-symbols-outlined text-base">groups</span>
                            {{ number_format($itemsCount) }} موظف
                        </span>
                        @if($cycle->locked_at)
                            <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 ring-1 ring-white/15">
                                <span class="material-symbols-outlined text-base">event_available</span>
                                قُفل بتاريخ {{ $cycle->locked_at->format('Y-m-d H:i') }}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="flex flex-col gap-3 md:items-end">
                    <span class="inline-flex items-center gap-2 self-start rounded-full px-4 py-2 text-sm font-bold md:self-end {{ $statusMeta['class'] }}">
                        <span class="material-symbols-outlined text-base">{{ $statusMeta['icon'] }}</span>
                        {{ $statusMeta['label'] }}
                    </span>

                    @can('manage-payroll')
                        <div class="flex flex-wrap gap-2 md:justify-end">
                            @if($cycle->status === 'draft')
                                <form method="POST" action="{{ route('payroll.transition', $cycle) }}" onsubmit="return confirm('إرسال المسير للمراجعة؟ لن يُقفل بعد - تبقى البيانات قابلة للتعديل حتى القفل.');">
                                    @csrf
                                    <input type="hidden" name="status" value="under_review">
                                    <button class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2 text-sm font-bold text-primary shadow-sm transition hover:bg-white/90">
                                        <span class="material-symbols-outlined text-base">send</span>
                                        إرسال للمراجعة
                                    </button>
                                </form>
                            @elseif($cycle->status === 'under_review')
                                <form method="POST" action="{{ route('payroll.transition', $cycle) }}" onsubmit="return confirm('اعتماد المسير؟\nالإجمالي: {{ number_format($grossTotal, 2) }}\nالصافي: {{ number_format($netTotal, 2) }}\nعدد الموظفين: {{ $itemsCount }}');">
                                    @csrf
                                    <input type="hidden" name="status" value="approved">
                                    <button class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2 text-sm font-bold text-primary shadow-sm transition hover:bg-white/90">
                                        <span class="material-symbols-outlined text-base">done</span>
                                        اعتماد
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('payroll.transition', $cycle) }}" onsubmit="return confirm('إعادة المسير إلى مسودة؟');">
                                    @csrf
                                    <input type="hidden" name="status" value="draft">
                                    <button class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-4 py-2 text-sm font-bold text-white ring-1 ring-white/20 transition hover:bg-white/20">
                                        <span class="material-symbols-outlined text-base">undo</span>
                                        إعادة للمسودة
                                    </button>
                                </form>
                            @elseif($cycle->status === 'approved')
                                <form method="POST" action="{{ route('payroll.transition', $cycle) }}" onsubmit="return confirm('قفل المسير نهائياً؟\n\nبعد القفل لا يمكن تعديل أي بند - أي تصحيح يتم عبر مسير تسوية جديد.\nالصافي الإجمالي: {{ number_format($netTotal, 2) }} ريال لعدد {{ $itemsCount }} موظف.');">
                                    @csrf
                                    <input type="hidden" name="status" value="locked">
                                    <button class="inline-flex items-center gap-2 rounded-xl bg-green-600 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-green-700">
                                        <span class="material-symbols-outlined text-base">lock</span>
                                        قفل المسير
                                    </button>
                                </form>
                            @elseif($cycle->status === 'locked')
                                <a href="{{ route('payroll.export.mudad', $cycle) }}"
                                   class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2 text-sm font-bold text-primary shadow-sm transition hover:bg-white/90"
                                   title="صيغة أولية بنمط ملف حماية الأجور - تحقق من مواصفات مدد الرسمية قبل الرفع">
                                    <span class="material-symbols-outlined text-base">download</span>
                                    تصدير ملف مدد (CSV)
                                </a>
                                <form method="POST" action="{{ route('payroll.adjustment', $cycle) }}" onsubmit="return confirm('إنشاء مسير تسوية جديد مرتبط بهذا المسير المقفل؟');">
                                    @csrf
                                    <button class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-4 py-2 text-sm font-bold text-white ring-1 ring-white/20 transition hover:bg-white/20">
                                        <span class="material-symbols-outlined text-base">add_circle</span>
                                        إنشاء مسير تسوية
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endcan
                </div>
            </div>

            @if($cycle->adjustmentRuns->isNotEmpty())
                <div class="border-t border-white/10 bg-white/5 px-6 py-4 text-sm text-white/75">
                    <span class="font-bold text-white">مسيرات تسوية مرتبطة:</span>
                    @foreach($cycle->adjustmentRuns as $run)
                        <a href="{{ route('payroll.show', $run) }}" class="font-bold text-white underline decoration-white/40 underline-offset-4 hover:decoration-white">#{{ $run->id }}</a>@if(!$loop->last)، @endif
                    @endforeach
                </div>
            @endif
        </section>

        <section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach([
                ['label' => 'الإجمالي', 'value' => number_format($grossTotal, 2), 'caption' => 'قبل الاستقطاعات', 'icon' => 'account_balance_wallet', 'tone' => 'bg-secondary-fixed text-secondary'],
                ['label' => 'الاستقطاعات', 'value' => number_format($deductionsTotal, 2), 'caption' => 'استقطاعات داخلية', 'icon' => 'remove_circle', 'tone' => 'bg-error-container text-error'],
                ['label' => 'التأمينات', 'value' => number_format($gosiTotal, 2), 'caption' => 'حصة التأمينات المسجلة', 'icon' => 'shield', 'tone' => 'bg-yellow-100 text-yellow-800'],
                ['label' => 'الصافي', 'value' => number_format($netTotal, 2), 'caption' => 'المبلغ المستحق للصرف', 'icon' => 'payments', 'tone' => 'bg-primary-fixed text-primary'],
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
                <div>
                    <h3 class="flex items-center gap-2 text-lg font-black text-on-surface">
                        <span class="material-symbols-outlined text-primary">receipt_long</span>
                        بنود مسير الرواتب
                    </h3>
                    <p class="mt-1 text-sm text-on-surface-variant">تفاصيل الرواتب والبدلات والاستقطاعات لكل موظف في الدورة.</p>
                </div>
                <span class="rounded-full bg-surface-container-low px-3 py-1 text-xs font-bold text-on-surface-variant">{{ number_format($itemsCount) }} بند</span>
            </div>

            <div class="overflow-x-auto">
                <table class="app-table min-w-[1120px] text-start text-sm">
                    <thead>
                        <tr>
                            <th class="px-6 py-4 font-bold">الموظف</th>
                            <th class="px-6 py-4 font-bold">القسم</th>
                            <th class="px-6 py-4 text-end font-bold">الأساسي</th>
                            <th class="px-6 py-4 text-end font-bold">البدلات</th>
                            <th class="px-6 py-4 text-end font-bold">التأمينات</th>
                            <th class="px-6 py-4 text-end font-bold">الصافي</th>
                            <th class="px-6 py-4 text-center font-bold">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/35">
                        @forelse($cycle->items as $item)
                            @php($allowances = $item->housing_allowance + $item->transportation_allowance + $item->other_allowances)
                            <tr class="transition hover:bg-surface-container-low">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-fixed font-bold text-primary">
                                            {{ mb_substr($item->employee?->name_ar ?? '-', 0, 1) }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="truncate font-bold text-primary">{{ $item->employee?->name_ar }}</div>
                                            <div class="text-[11px] text-on-surface-variant">#{{ $item->employee?->employee_code ?? '-' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-on-surface-variant">{{ $item->employee?->department?->name_ar ?? '-' }}</td>
                                <td class="px-6 py-4 text-end font-tabular">{{ number_format($item->basic_salary, 2) }}</td>
                                <td class="px-6 py-4 text-end font-tabular text-green-600">+{{ number_format($allowances, 2) }}</td>
                                <td class="px-6 py-4 text-end font-tabular text-error">-{{ number_format($item->social_insurance_saudi, 2) }}</td>
                                <td class="px-6 py-4 text-end font-tabular font-black text-on-surface">{{ number_format($item->net_salary, 2) }}</td>
                                <td class="px-6 py-4 text-center">
                                    <a href="{{ route('payroll.payslip', [$cycle, $item]) }}" class="inline-flex items-center gap-2 rounded-xl bg-primary-fixed px-3 py-2 text-xs font-bold text-primary transition hover:bg-primary-fixed-dim">
                                        <span class="material-symbols-outlined text-base">description</span>
                                        قسيمة الراتب
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-on-surface-variant">لا توجد بنود رواتب لهذه الدورة</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
