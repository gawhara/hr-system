@extends('layouts.app')

@section('title', 'الإجازات')

@section('content')
    <div class="mx-auto max-w-7xl space-y-6">
        @if(session('status'))
            <div class="rounded-xl bg-surface-container-low px-4 py-3 text-sm font-bold text-primary ring-1 ring-outline-variant">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-sm font-bold text-red-800">{{ $errors->first() }}</div>
        @endif

        <section class="flex flex-col justify-between gap-4 rounded-3xl border border-outline-variant/50 bg-white p-6 shadow-[0_16px_38px_rgba(25,28,30,0.05)] md:flex-row md:items-end">
            <div>
                <p class="font-label text-xs font-bold uppercase tracking-[0.18em] text-secondary">Leave Management</p>
                <h2 class="mt-2 text-3xl font-black text-on-surface">إدارة الإجازات</h2>
                <p class="mt-2 text-sm text-on-surface-variant">نظرة عامة على طلبات الموظفين وإدارة أرصدة الإجازات السنوية.</p>
            </div>
            <a href="{{ route('leaves.create') }}" class="stitch-btn-primary flex items-center gap-2 px-6 py-3">
                <span class="material-symbols-outlined">add</span>
                <span>طلب إجازة جديد</span>
            </a>
        </section>

        <div class="grid grid-cols-1 gap-5 xl:grid-cols-12">
            <aside class="app-card p-5 xl:col-span-4">
                <div class="mb-5 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-black text-on-surface">رصيد الإجازات</h3>
                        <p class="mt-1 text-xs text-on-surface-variant">عرض مرجعي سريع للأنواع الشائعة</p>
                    </div>
                    <span class="rounded-full bg-secondary-fixed px-3 py-1 text-xs font-bold text-secondary">{{ now()->format('Y') }}</span>
                </div>
                <div class="space-y-3">
                    @foreach(['سنوية' => 21, 'مرضية' => 30, 'طارئة' => 5] as $label => $days)
                        <div class="rounded-2xl border border-outline-variant/35 bg-surface-container-low p-4">
                            <div class="mb-3 flex items-center justify-between">
                                <div>
                                    <p class="text-xs text-on-surface-variant">{{ $label }}</p>
                                    <p class="font-bold">{{ $days }} يوم</p>
                                </div>
                                <span class="font-tabular text-lg font-black text-primary">{{ $days }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-surface-container">
                                <div class="h-full rounded-full bg-primary" style="width: {{ min(100, $days * 3) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </aside>

            <section class="app-card p-5 xl:col-span-8">
                <div class="mb-5 flex items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-black text-on-surface">تصفية الطلبات</h3>
                        <p class="mt-1 text-xs text-on-surface-variant">الحالة الحالية للطلبات</p>
                    </div>
                    <span class="rounded-full bg-surface-container-low px-3 py-1 text-xs font-bold text-on-surface-variant">{{ $leaveRequests->total() }} طلب</span>
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

        <section class="app-card overflow-hidden">
            <div class="flex items-center justify-between border-b border-outline-variant/50 bg-white p-5">
                <h3 class="text-lg font-black text-on-surface">طلبات بانتظار المعالجة</h3>
                <span class="rounded-full bg-error-container px-3 py-1 text-xs font-bold text-on-error-container">{{ $leaveRequests->total() }} طلب</span>
            </div>
            <div class="overflow-x-auto">
                <table class="app-table min-w-[960px] text-start text-sm">
                    <thead>
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
                            <tr class="transition hover:bg-surface-container-low">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-fixed font-bold text-primary">{{ mb_substr($leave->employee?->name_ar ?? '-', 0, 1) }}</div>
                                        <div>
                                            <p class="font-bold">{{ $leave->employee?->name_ar }}</p>
                                            <p class="text-[10px] text-on-surface-variant">{{ $leave->employee?->department?->name_ar }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4"><span class="app-status-chip bg-surface-container text-primary">{{ $leave->leaveType?->name_ar }}</span></td>
                                <td class="px-6 py-4 font-tabular">{{ $leave->starts_on->format('Y-m-d') }} - {{ $leave->ends_on->format('Y-m-d') }}</td>
                                <td class="px-6 py-4 font-tabular font-bold">{{ number_format($leave->days, 2) }}</td>
                                <td class="px-6 py-4">
                                    <span class="app-status-chip @if($leave->status === 'approved') bg-green-100 text-green-700 @elseif($leave->status === 'rejected') bg-red-100 text-red-700 @else bg-yellow-100 text-yellow-800 @endif">
                                        {{ ['pending' => 'معلق', 'approved' => 'موافق عليه', 'rejected' => 'مرفوض'][$leave->status] ?? $leave->status }}
                                    </span>
                                </td>
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
                            <tr><td colspan="6" class="px-6 py-12 text-center text-on-surface-variant">لا توجد طلبات إجازة</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-outline-variant/50 p-4">{{ $leaveRequests->links() }}</div>
        </section>
    </div>
@endsection
