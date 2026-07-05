@extends('layouts.app')

@section('title', 'التقارير والتحليلات')

@section('content')
    @php
        $largestCompany = $companyBreakdown->sortByDesc('employees')->first();
        $topDepartment = $departmentBreakdown->first();
        $riskTone = $metrics['pendingLeaves'] > 0 ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-700';
    @endphp

    <div class="mx-auto max-w-7xl space-y-6">
        <section class="flex flex-col justify-between gap-5 rounded-3xl bg-primary p-6 text-white shadow-[0_20px_50px_rgba(61,32,143,0.18)] lg:flex-row lg:items-end">
            <div class="max-w-3xl">
                <p class="font-label text-xs font-bold uppercase tracking-[0.18em] text-white/65">Reports</p>
                <h2 class="mt-3 text-3xl font-black leading-tight md:text-4xl">لوحة التقارير والتحليلات</h2>
                <p class="mt-3 text-sm leading-7 text-white/75">
                    نظرة تنفيذية على القوى العاملة والرواتب والإجازات عبر الشركات المتاحة لحسابك.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-3 text-sm sm:flex sm:flex-wrap sm:justify-end">
                <div class="rounded-2xl bg-white/10 px-4 py-3 ring-1 ring-white/15">
                    <span class="block text-white/60">الشركات</span>
                    <strong class="mt-1 block font-tabular text-2xl font-black">{{ number_format($metrics['companies']) }}</strong>
                </div>
                <div class="rounded-2xl bg-white/10 px-4 py-3 ring-1 ring-white/15">
                    <span class="block text-white/60">التوطين</span>
                    <strong class="mt-1 block font-tabular text-2xl font-black">{{ $metrics['saudization'] }}%</strong>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach([
                ['label' => 'إجمالي الموظفين', 'value' => number_format($metrics['headcount']), 'caption' => 'عبر '.$metrics['companies'].' شركات تابعة', 'icon' => 'groups', 'tone' => 'bg-primary-fixed text-primary', 'badge' => 'Headcount', 'badgeTone' => 'bg-surface-container text-on-surface-variant'],
                ['label' => 'صافي الرواتب', 'value' => number_format($metrics['payroll'], 2).' ر.س', 'caption' => 'إجمالي صافي الرواتب المسجلة', 'icon' => 'payments', 'tone' => 'bg-secondary-fixed text-secondary', 'badge' => 'Payroll', 'badgeTone' => 'bg-secondary-fixed text-secondary'],
                ['label' => 'نسبة التوطين', 'value' => $metrics['saudization'].'%', 'caption' => 'حسب بيانات الموظفين الحالية', 'icon' => 'workspace_premium', 'tone' => 'bg-green-100 text-green-700', 'badge' => 'نطاقات', 'badgeTone' => 'bg-green-100 text-green-700'],
                ['label' => 'إجازات معلقة', 'value' => number_format($metrics['pendingLeaves']), 'caption' => 'طلبات بانتظار الاعتماد', 'icon' => 'pending_actions', 'tone' => 'bg-error-container text-error', 'badge' => 'مراجعة', 'badgeTone' => $riskTone],
            ] as $metric)
                <div class="app-kpi-card p-5">
                    <div class="flex items-start justify-between gap-4">
                        <span class="material-symbols-outlined rounded-xl p-2 {{ $metric['tone'] }}">{{ $metric['icon'] }}</span>
                        <span class="rounded-full px-3 py-1 text-[11px] font-bold {{ $metric['badgeTone'] }}">{{ $metric['badge'] }}</span>
                    </div>
                    <p class="mt-5 text-xs font-bold text-on-surface-variant">{{ $metric['label'] }}</p>
                    <strong class="mt-2 block truncate font-tabular text-3xl font-black text-on-surface">{{ $metric['value'] }}</strong>
                    <p class="mt-2 text-xs text-on-surface-variant">{{ $metric['caption'] }}</p>
                </div>
            @endforeach
        </section>

        <section class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <div class="app-card overflow-hidden xl:col-span-2">
                <div class="flex flex-col justify-between gap-3 border-b border-outline-variant/50 bg-white p-5 md:flex-row md:items-center">
                    <div>
                        <h3 class="flex items-center gap-2 text-lg font-black text-on-surface">
                            <span class="material-symbols-outlined text-primary">bar_chart</span>
                            توزيع الموظفين حسب الشركة
                        </h3>
                        <p class="mt-1 text-sm text-on-surface-variant">مقارنة حجم القوى العاملة بين شركات المجموعة.</p>
                    </div>
                    @if($largestCompany)
                        <span class="app-status-chip bg-primary-fixed text-primary">الأكبر: {{ $largestCompany['name'] }}</span>
                    @endif
                </div>

                <div class="p-5">
                    <div class="grid min-h-[18rem] grid-cols-[auto_1fr] gap-4">
                        <div class="flex flex-col justify-between pb-10 text-end font-tabular text-[11px] text-on-surface-variant">
                            <span>100%</span>
                            <span>75%</span>
                            <span>50%</span>
                            <span>25%</span>
                            <span>0%</span>
                        </div>
                        <div class="flex items-end gap-3 overflow-x-auto border-b border-outline-variant/60 pb-4 custom-scrollbar">
                            @forelse($companyBreakdown as $company)
                                <div class="group flex h-72 min-w-24 flex-1 flex-col items-center justify-end gap-3">
                                    <div class="relative flex h-full w-full max-w-24 items-end overflow-hidden rounded-t-2xl bg-surface-container-low">
                                        <div class="chart-bar-transition w-full rounded-t-2xl bg-primary" style="height: {{ max($company['percentage'], 5) }}%"></div>
                                        <div class="absolute inset-x-2 bottom-2 rounded-xl bg-white/90 px-2 py-1 text-center text-[11px] font-black text-primary opacity-0 shadow-sm transition group-hover:opacity-100">
                                            {{ number_format($company['employees']) }}
                                        </div>
                                    </div>
                                    <div class="w-full text-center">
                                        <p class="truncate text-xs font-black text-on-surface">{{ $company['name_en'] ?: $company['name'] }}</p>
                                        <p class="truncate text-[11px] text-on-surface-variant">{{ $company['percentage'] }}%</p>
                                    </div>
                                </div>
                            @empty
                                <div class="flex min-h-72 flex-1 items-center justify-center rounded-2xl bg-surface-container-low text-sm text-on-surface-variant">
                                    لا توجد بيانات شركات متاحة.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <aside class="app-card p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-black text-on-surface">الأقسام الأعلى كثافة</h3>
                        <p class="mt-1 text-sm text-on-surface-variant">حسب عدد الموظفين.</p>
                    </div>
                    <span class="material-symbols-outlined rounded-xl bg-secondary-fixed p-2 text-secondary">monitoring</span>
                </div>

                <div class="mt-6 space-y-5">
                    @forelse($departmentBreakdown as $department)
                        @php($percentage = $metrics['headcount'] > 0 ? round(($department->total / $metrics['headcount']) * 100) : 0)
                        <div>
                            <div class="mb-2 flex items-center justify-between gap-3 text-sm">
                                <span class="truncate font-bold text-on-surface">{{ $department->name ?? 'غير محدد' }}</span>
                                <span class="font-tabular font-black text-primary">{{ number_format($department->total) }}</span>
                            </div>
                            <div class="h-2.5 overflow-hidden rounded-full bg-surface-container-high">
                                <div class="h-full rounded-full bg-secondary" style="width: {{ max($percentage, 4) }}%"></div>
                            </div>
                            <p class="mt-1 text-end font-tabular text-[11px] text-on-surface-variant">{{ $percentage }}%</p>
                        </div>
                    @empty
                        <div class="rounded-2xl bg-surface-container-low p-4 text-sm text-on-surface-variant">لا توجد بيانات أقسام بعد.</div>
                    @endforelse
                </div>
            </aside>
        </section>

        <section class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <div class="app-card overflow-hidden xl:col-span-2">
                <div class="flex flex-col justify-between gap-3 border-b border-outline-variant/50 bg-white p-5 md:flex-row md:items-center">
                    <h3 class="flex items-center gap-2 text-lg font-black text-on-surface">
                        <span class="material-symbols-outlined text-primary">table_chart</span>
                        أحدث الموظفين
                    </h3>
                    <span class="rounded-full bg-surface-container-low px-3 py-1 text-xs font-bold text-on-surface-variant">آخر {{ $recentEmployees->count() }} سجلات</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="app-table min-w-[900px] text-start text-sm">
                        <thead>
                            <tr>
                                <th class="px-6 py-4 font-bold">الموظف</th>
                                <th class="px-6 py-4 font-bold">الشركة</th>
                                <th class="px-6 py-4 font-bold">القسم</th>
                                <th class="px-6 py-4 font-bold">بداية العقد</th>
                                <th class="px-6 py-4 text-center font-bold">عرض</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/35">
                            @forelse($recentEmployees as $employee)
                                <tr class="transition hover:bg-surface-container-low">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-fixed font-bold text-primary">{{ mb_substr($employee->name_ar, 0, 1) }}</div>
                                            <div class="min-w-0">
                                                <div class="truncate font-bold text-primary">{{ $employee->name_ar }}</div>
                                                <div class="text-[11px] text-on-surface-variant">#{{ $employee->employee_code }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">{{ $employee->company?->name_ar ?? '-' }}</td>
                                    <td class="px-6 py-4">{{ $employee->department?->name_ar ?? '-' }}</td>
                                    <td class="px-6 py-4 font-tabular">{{ $employee->contract_start_date?->format('Y-m-d') ?? '-' }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <a href="{{ route('employees.show', $employee) }}" class="inline-flex items-center justify-center rounded-xl bg-primary-fixed p-2 text-primary transition hover:bg-primary-fixed-dim" title="عرض ملف الموظف">
                                            <span class="material-symbols-outlined">visibility</span>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-on-surface-variant">لا توجد سجلات موظفين حديثة.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <aside class="app-card overflow-hidden">
                <div class="bg-primary p-5 text-white">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/12 ring-1 ring-white/15">
                        <span class="material-symbols-outlined">insights</span>
                    </div>
                    <h3 class="mt-4 text-xl font-black">رؤى تنفيذية</h3>
                    <p class="mt-2 text-sm leading-7 text-white/75">ملخص سريع لأبرز المؤشرات التشغيلية من قاعدة بيانات الموارد البشرية.</p>
                </div>
                <div class="space-y-3 p-5">
                    <div class="rounded-2xl bg-surface-container-low p-4">
                        <p class="text-xs font-bold text-on-surface-variant">أعلى شركة من حيث الموظفين</p>
                        <strong class="mt-1 block text-on-surface">{{ $largestCompany['name'] ?? 'غير متاح' }}</strong>
                    </div>
                    <div class="rounded-2xl bg-surface-container-low p-4">
                        <p class="text-xs font-bold text-on-surface-variant">أعلى قسم كثافة</p>
                        <strong class="mt-1 block text-on-surface">{{ $topDepartment?->name ?? 'غير محدد' }}</strong>
                    </div>
                    <div class="rounded-2xl bg-surface-container-low p-4">
                        <p class="text-xs font-bold text-on-surface-variant">طلبات الإجازة المعلقة</p>
                        <strong class="mt-1 block font-tabular text-on-surface">{{ number_format($metrics['pendingLeaves']) }}</strong>
                    </div>
                </div>
            </aside>
        </section>
    </div>
@endsection
