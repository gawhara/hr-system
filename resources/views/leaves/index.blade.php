@extends('layouts.app')

@section('title', 'الإجازات')

@section('content')
    <div class="space-y-6">
        @if(session('status'))
            <div class="rounded-xl bg-surface-container-low px-4 py-3 text-sm font-bold text-primary ring-1 ring-outline-variant">{{ session('status') }}</div>
        @endif

        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
            <div>
                <h2 class="text-4xl font-black text-primary">إدارة الإجازات</h2>
                <p class="mt-2 text-on-surface-variant">نظرة عامة على طلبات الموظفين وإدارة رصيد الإجازات السنوي.</p>
            </div>
            <a href="{{ route('leaves.create') }}" class="stitch-btn-primary flex items-center gap-2 px-6 py-3">طلب إجازة جديد</a>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
            <aside class="glass-card rounded-2xl p-6 xl:col-span-4">
                <div class="mb-6 flex items-center justify-between">
                    <h3 class="text-lg font-bold">رصيد الإجازات</h3>
                    <span class="rounded-full bg-secondary-container px-3 py-1 text-xs font-bold text-on-secondary-container">{{ now()->format('Y') }}</span>
                </div>
                <div class="space-y-4">
                    @foreach(['سنوية' => 21, 'مرضية' => 30, 'طارئة' => 5] as $label => $days)
                        <div class="rounded-xl bg-surface-container p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs text-on-surface-variant">{{ $label }}</p>
                                    <p class="font-bold">{{ $days }} يوم</p>
                                </div>
                                <div class="h-1 w-16 overflow-hidden rounded-full bg-outline-variant">
                                    <div class="h-full bg-primary" style="width: {{ min(100, $days * 3) }}%"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </aside>

            <section class="glass-card rounded-2xl p-6 xl:col-span-8">
                <div class="mb-6 flex items-center justify-between">
                    <h3 class="text-lg font-bold">تصفية الطلبات</h3>
                    <span class="text-sm text-on-surface-variant">الحالة الحالية</span>
                </div>
                <form class="flex flex-col gap-3 sm:flex-row">
                    <select name="status" class="stitch-input px-4 py-3">
                        <option value="">كل الحالات</option>
                        <option value="pending" @selected($status === 'pending')>معلق</option>
                        <option value="approved" @selected($status === 'approved')>موافق عليه</option>
                        <option value="rejected" @selected($status === 'rejected')>مرفوض</option>
                    </select>
                    <button class="stitch-btn-primary px-5 py-3">تصفية</button>
                </form>
            </section>
        </div>

        <div class="glass-card overflow-hidden rounded-2xl">
            <div class="flex items-center justify-between border-b border-outline-variant p-6">
                <h3 class="text-lg font-bold">طلبات بانتظار الموافقة</h3>
                <span class="rounded-full bg-error-container px-3 py-1 text-xs font-bold text-on-error-container">{{ $leaveRequests->total() }} طلب</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-right text-sm">
                    <thead class="bg-surface-container-low text-on-surface-variant">
                        <tr>
                            <th class="px-6 py-4 font-bold">الموظف</th>
                            <th class="px-6 py-4 font-bold">نوع الإجازة</th>
                            <th class="px-6 py-4 font-bold">الفترة</th>
                            <th class="px-6 py-4 font-bold">الأيام</th>
                            <th class="px-6 py-4 font-bold">الحالة</th>
                            <th class="px-6 py-4 font-bold">إجراء</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/30">
                        @forelse($leaveRequests as $leave)
                            <tr class="transition hover:bg-surface-container">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-surface-container-highest font-bold text-primary">{{ mb_substr($leave->employee?->name_ar ?? '-', 0, 1) }}</div>
                                        <div>
                                            <p class="font-bold">{{ $leave->employee?->name_ar }}</p>
                                            <p class="text-[10px] text-on-surface-variant">{{ $leave->employee?->department?->name_ar }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4"><span class="rounded-full bg-surface-container-highest px-3 py-1 text-xs text-primary">{{ $leave->leaveType?->name_ar }}</span></td>
                                <td class="px-6 py-4">{{ $leave->starts_on->format('Y-m-d') }} - {{ $leave->ends_on->format('Y-m-d') }}</td>
                                <td class="px-6 py-4 font-bold">{{ number_format($leave->days, 2) }}</td>
                                <td class="px-6 py-4">{{ ['pending' => 'معلق', 'approved' => 'موافق عليه', 'rejected' => 'مرفوض'][$leave->status] ?? $leave->status }}</td>
                                <td class="px-6 py-4">
                                    @if($leave->status === 'pending')
                                        <div class="flex gap-2">
                                            <form method="POST" action="{{ route('leaves.approve', $leave) }}">@csrf<button class="rounded-lg bg-primary px-4 py-2 text-xs font-bold text-on-primary">موافقة</button></form>
                                            <form method="POST" action="{{ route('leaves.reject', $leave) }}">@csrf<button class="rounded-lg border border-outline px-4 py-2 text-xs font-bold text-on-surface-variant hover:bg-error-container hover:text-error">رفض</button></form>
                                        </div>
                                    @else
                                        <span class="text-xs text-on-surface-variant">{{ $leave->approver?->name ?? '-' }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-10 text-center text-on-surface-variant">لا توجد طلبات إجازة</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-outline-variant p-4">{{ $leaveRequests->links() }}</div>
        </div>
    </div>
@endsection
