@extends('layouts.app')

@section('title', 'الحضور')

@section('content')
    <div class="mx-auto max-w-7xl space-y-6">
        <section class="flex flex-col justify-between gap-4 rounded-3xl border border-outline-variant/50 bg-white p-6 shadow-[0_16px_38px_rgba(25,28,30,0.05)] md:flex-row md:items-end">
            <div>
                <p class="font-label text-xs font-bold uppercase tracking-[0.18em] text-secondary">Attendance</p>
                <h2 class="mt-2 text-3xl font-black text-on-surface">لوحة مراقبة الحضور</h2>
                <p class="mt-2 text-sm text-on-surface-variant">متابعة الحضور والتأخير وسجلات اليوم.</p>
            </div>
            <form class="flex flex-col gap-3 sm:flex-row">
                <input name="date" type="date" value="{{ $date }}" class="stitch-input px-4 py-3">
                <button class="stitch-btn-primary px-5 py-3">عرض اليوم</button>
            </form>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach([
                ['label' => 'الموظفون', 'value' => $summary['employees'], 'icon' => 'groups', 'tone' => 'bg-primary-fixed text-primary'],
                ['label' => 'حاضر', 'value' => $summary['present'], 'icon' => 'check_circle', 'tone' => 'bg-green-100 text-green-700'],
                ['label' => 'متأخر', 'value' => $summary['late'], 'icon' => 'schedule', 'tone' => 'bg-yellow-100 text-yellow-800'],
                ['label' => 'غائب', 'value' => $summary['absent'], 'icon' => 'cancel', 'tone' => 'bg-red-100 text-red-700'],
            ] as $metric)
                <div class="app-kpi-card p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold text-on-surface-variant">{{ $metric['label'] }}</p>
                            <strong class="mt-2 block font-tabular text-3xl font-black text-on-surface">{{ number_format($metric['value']) }}</strong>
                        </div>
                        <span class="material-symbols-outlined rounded-xl p-2 {{ $metric['tone'] }}">{{ $metric['icon'] }}</span>
                    </div>
                </div>
            @endforeach
        </section>

        <section class="app-card overflow-hidden">
            <div class="flex items-center justify-between border-b border-outline-variant/50 bg-white p-5">
                <div>
                    <h3 class="text-lg font-black text-on-surface">سجلات الحضور</h3>
                    <p class="mt-1 text-xs text-on-surface-variant">تاريخ العرض: <span class="font-tabular">{{ $date }}</span></p>
                </div>
                <span class="rounded-full bg-surface-container-low px-3 py-1 text-xs font-bold text-on-surface-variant">{{ number_format($records->total()) }} سجل</span>
            </div>
            <div class="overflow-x-auto">
                <table class="app-table min-w-[820px] text-start text-sm">
                    <thead>
                        <tr>
                            <th class="px-6 py-4 font-bold">الموظف</th>
                            <th class="px-6 py-4 font-bold">الفرع</th>
                            <th class="px-6 py-4 font-bold">الدخول</th>
                            <th class="px-6 py-4 font-bold">الخروج</th>
                            <th class="px-6 py-4 font-bold">التأخير</th>
                            <th class="px-6 py-4 font-bold">الحالة</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/30">
                        @forelse($records as $record)
                            <tr class="transition hover:bg-surface-container-low">
                                <td class="px-6 py-4 font-bold">{{ $record->employee?->name_ar }}</td>
                                <td class="px-6 py-4">{{ $record->employee?->branch?->name_ar }}</td>
                                <td class="px-6 py-4 font-tabular">{{ $record->check_in ?? '-' }}</td>
                                <td class="px-6 py-4 font-tabular">{{ $record->check_out ?? '-' }}</td>
                                <td class="px-6 py-4"><span class="font-tabular">{{ $record->late_minutes }}</span> دقيقة</td>
                                <td class="px-6 py-4">
                                    <span class="app-status-chip {{ $record->status === 'present' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                        {{ $record->status === 'present' ? 'حاضر' : 'غائب' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-12 text-center text-on-surface-variant">لا توجد سجلات لهذا اليوم</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-outline-variant/50 p-4">{{ $records->links() }}</div>
        </section>
    </div>
@endsection
