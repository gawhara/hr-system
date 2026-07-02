@extends('layouts.app')

@section('title', 'الحضور')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
            <div>
                <h2 class="text-4xl font-black text-primary">لوحة مراقبة الحضور</h2>
                <p class="mt-2 text-on-surface-variant">متابعة الحضور والتأخير وسجلات اليوم</p>
            </div>
            <form class="flex gap-3">
                <input name="date" type="date" value="{{ $date }}" class="stitch-input px-4 py-3">
                <button class="stitch-btn-primary px-5 py-3">عرض اليوم</button>
            </form>
        </div>

        <section class="grid gap-4 sm:grid-cols-4">
            @foreach(['الموظفون' => $summary['employees'], 'حاضر' => $summary['present'], 'متأخر' => $summary['late'], 'غائب' => $summary['absent']] as $label => $value)
                <div class="glass-card rounded-2xl p-6">
                    <div class="text-sm text-on-surface-variant">{{ $label }}</div>
                    <div class="mt-2 text-3xl font-black text-on-surface">{{ number_format($value) }}</div>
                </div>
            @endforeach
        </section>

        <div class="glass-card overflow-hidden rounded-2xl">
            <table class="w-full text-right text-sm">
                <thead class="bg-surface-container-low text-on-surface-variant">
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
                        <tr class="transition hover:bg-surface-container">
                            <td class="px-6 py-4 font-bold">{{ $record->employee?->name_ar }}</td>
                            <td class="px-6 py-4">{{ $record->employee?->branch?->name_ar }}</td>
                            <td class="px-6 py-4">{{ $record->check_in ?? '-' }}</td>
                            <td class="px-6 py-4">{{ $record->check_out ?? '-' }}</td>
                            <td class="px-6 py-4">{{ $record->late_minutes }} دقيقة</td>
                            <td class="px-6 py-4"><span class="rounded-full bg-surface-container-highest px-3 py-1 text-xs font-bold text-primary">{{ $record->status === 'present' ? 'حاضر' : 'غائب' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-10 text-center text-on-surface-variant">لا توجد سجلات لهذا اليوم</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="border-t border-outline-variant p-4">{{ $records->links() }}</div>
        </div>
    </div>
@endsection
