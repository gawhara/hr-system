@extends('layouts.app')

@section('title', 'تفاصيل الرواتب')

@section('content')
    <div class="space-y-6">
        @if(session('status'))
            <div class="rounded-2xl border border-green-300 bg-green-50 p-4 text-sm font-bold text-green-800">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="rounded-2xl border border-red-300 bg-red-50 p-4 text-sm font-bold text-red-800">{{ $errors->first() }}</div>
        @endif

        <div class="glass-card rounded-2xl p-6">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <div class="text-sm text-on-surface-variant">
                        تفاصيل الرواتب
                        @if($cycle->parent_cycle_id)
                            &middot; <a href="{{ route('payroll.show', $cycle->parent_cycle_id) }}" class="font-bold text-primary hover:underline">مسير تسوية للمسير رقم {{ $cycle->parent_cycle_id }}</a>
                        @endif
                    </div>
                    <h2 class="mt-2 text-3xl font-black text-primary">{{ $cycle->company?->name_ar }} - {{ $cycle->year }} / {{ str_pad($cycle->month, 2, '0', STR_PAD_LEFT) }}</h2>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    @php
                        $statusMeta = [
                            'draft' => ['مسودة', 'bg-surface-container text-on-surface-variant'],
                            'under_review' => ['قيد المراجعة', 'bg-yellow-100 text-yellow-800'],
                            'approved' => ['معتمد', 'bg-blue-100 text-blue-800'],
                            'locked' => ['مقفل', 'bg-green-100 text-green-800'],
                        ][$cycle->status] ?? [$cycle->status, 'bg-surface-container text-on-surface-variant'];
                    @endphp
                    <span class="rounded-full px-4 py-2 text-sm font-bold {{ $statusMeta[1] }}">{{ $statusMeta[0] }}</span>

                    @can('manage-payroll')
                        @if($cycle->status === 'draft')
                            <form method="POST" action="{{ route('payroll.transition', $cycle) }}" onsubmit="return confirm('إرسال المسير للمراجعة؟ لن يُقفل بعد — تبقى البيانات قابلة للتعديل حتى القفل.');">
                                @csrf
                                <input type="hidden" name="status" value="under_review">
                                <button class="stitch-btn-primary px-4 py-2 text-sm">إرسال للمراجعة</button>
                            </form>
                        @elseif($cycle->status === 'under_review')
                            <form method="POST" action="{{ route('payroll.transition', $cycle) }}" onsubmit="return confirm('اعتماد المسير؟\nالإجمالي: {{ number_format($cycle->items->sum('gross_total'), 2) }}\nالصافي: {{ number_format($cycle->items->sum('net_salary'), 2) }}\nعدد الموظفين: {{ $cycle->items->count() }}');">
                                @csrf
                                <input type="hidden" name="status" value="approved">
                                <button class="stitch-btn-primary px-4 py-2 text-sm">اعتماد</button>
                            </form>
                            <form method="POST" action="{{ route('payroll.transition', $cycle) }}" onsubmit="return confirm('إعادة المسير إلى مسودة؟');">
                                @csrf
                                <input type="hidden" name="status" value="draft">
                                <button class="rounded-xl border border-outline px-4 py-2 text-sm font-bold hover:bg-surface-container">إعادة للمسودة</button>
                            </form>
                        @elseif($cycle->status === 'approved')
                            <form method="POST" action="{{ route('payroll.transition', $cycle) }}" onsubmit="return confirm('قفل المسير نهائياً؟\n\nبعد القفل لا يمكن تعديل أي بند — أي تصحيح يتم عبر مسير تسوية جديد.\nالصافي الإجمالي: {{ number_format($cycle->items->sum('net_salary'), 2) }} ريال لعدد {{ $cycle->items->count() }} موظف.');">
                                @csrf
                                <input type="hidden" name="status" value="locked">
                                <button class="rounded-xl bg-green-700 px-4 py-2 text-sm font-bold text-white hover:bg-green-800">قفل المسير</button>
                            </form>
                        @elseif($cycle->status === 'locked')
                            <form method="POST" action="{{ route('payroll.adjustment', $cycle) }}" onsubmit="return confirm('إنشاء مسير تسوية جديد مرتبط بهذا المسير المقفل؟');">
                                @csrf
                                <button class="rounded-xl border border-outline px-4 py-2 text-sm font-bold hover:bg-surface-container">إنشاء مسير تسوية</button>
                            </form>
                        @endif
                    @endcan
                </div>
            </div>
            @if($cycle->locked_at)
                <p class="mt-3 text-xs text-on-surface-variant">قُفل بتاريخ {{ $cycle->locked_at->format('Y-m-d H:i') }}</p>
            @endif
            @if($cycle->adjustmentRuns->isNotEmpty())
                <p class="mt-3 text-xs text-on-surface-variant">
                    مسيرات تسوية مرتبطة:
                    @foreach($cycle->adjustmentRuns as $run)
                        <a href="{{ route('payroll.show', $run) }}" class="font-bold text-primary hover:underline">#{{ $run->id }}</a>@if(!$loop->last)، @endif
                    @endforeach
                </p>
            @endif
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <div class="glass-card rounded-2xl p-6"><div class="text-sm text-on-surface-variant">الإجمالي</div><div class="mt-2 text-2xl font-black">{{ number_format($cycle->items->sum('gross_total'), 2) }}</div></div>
            <div class="glass-card rounded-2xl p-6"><div class="text-sm text-on-surface-variant">الاستقطاعات</div><div class="mt-2 text-2xl font-black">{{ number_format($cycle->items->sum('total_deductions'), 2) }}</div></div>
            <div class="glass-card rounded-2xl p-6"><div class="text-sm text-on-surface-variant">الصافي</div><div class="mt-2 text-2xl font-black text-primary">{{ number_format($cycle->items->sum('net_salary'), 2) }}</div></div>
        </div>

        <div class="glass-card overflow-hidden rounded-2xl">
            <table class="w-full text-right text-sm">
                <thead class="bg-surface-container-low text-on-surface-variant">
                    <tr>
                        <th class="px-6 py-4 font-bold">الموظف</th>
                        <th class="px-6 py-4 font-bold">القسم</th>
                        <th class="px-6 py-4 font-bold">الأساسي</th>
                        <th class="px-6 py-4 font-bold">البدلات</th>
                        <th class="px-6 py-4 font-bold">التأمينات</th>
                        <th class="px-6 py-4 font-bold">الصافي</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/30">
                    @foreach($cycle->items as $item)
                        <tr class="transition hover:bg-surface-container">
                            <td class="px-6 py-4 font-bold">{{ $item->employee?->name_ar }}</td>
                            <td class="px-6 py-4">{{ $item->employee?->department?->name_ar }}</td>
                            <td class="px-6 py-4">{{ number_format($item->basic_salary, 2) }}</td>
                            <td class="px-6 py-4">{{ number_format($item->housing_allowance + $item->transportation_allowance + $item->other_allowances, 2) }}</td>
                            <td class="px-6 py-4">{{ number_format($item->social_insurance_saudi, 2) }}</td>
                            <td class="px-6 py-4 font-black text-primary">{{ number_format($item->net_salary, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
