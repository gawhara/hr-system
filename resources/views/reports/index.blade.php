@extends('layouts.app')

@section('title', 'التقارير والتحليلات')

@section('content')
    <div class="space-y-8">
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <div>
                <h2 class="text-2xl font-bold text-primary">التقارير والتحليلات الشاملة</h2>
                <p class="mt-1 text-on-surface-variant">نظرة عامة على أداء القوى العاملة عبر جميع شركات المجموعة</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2 rounded-lg border border-outline-variant bg-white px-3 py-2 shadow-sm">
                    <span class="material-symbols-outlined text-sm text-outline">calendar_today</span>
                    <select class="cursor-pointer border-none bg-transparent text-sm focus:ring-0">
                        <option>آخر 12 شهر</option>
                        <option>الربع الحالي</option>
                        <option>السنة الحالية</option>
                    </select>
                </div>
                <button class="flex items-center gap-2 rounded-lg bg-primary px-4 py-2 font-semibold text-on-primary shadow-md transition-all hover:bg-primary-container active:scale-95">
                    <span class="material-symbols-outlined text-sm">download</span>
                    <span class="text-sm">تحميل التقرير</span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-4">
            @foreach([
                ['إجمالي الموظفين', number_format($metrics['headcount']), 'عبر '.$metrics['companies'].' شركات تابعة', 'groups', 'bg-primary-fixed text-on-primary-fixed', '+4.2%', 'text-emerald-600 bg-emerald-50'],
                ['إجمالي الرواتب الشهري', number_format($metrics['payroll'], 2).' ر.س', 'صافي الرواتب المسجلة', 'account_balance_wallet', 'bg-secondary-fixed text-on-secondary-fixed', '+1.8%', 'text-amber-600 bg-amber-50'],
                ['نسبة التوطين', $metrics['saudization'].'%', 'حسب بيانات الموظفين الحالية', 'star', 'bg-tertiary-fixed text-on-tertiary-fixed', 'نطاقات', 'text-emerald-600 bg-emerald-50'],
                ['طلبات الإجازة المعلقة', number_format($metrics['pendingLeaves']), 'بانتظار الاعتماد', 'person_remove', 'bg-error-container text-on-error-container', 'مراجعة', 'text-red-600 bg-red-50'],
            ] as [$label, $value, $caption, $icon, $tone, $badge, $badgeTone])
                <div class="rounded-xl border border-outline-variant bg-white p-6 shadow-sm transition-shadow hover:shadow-md">
                    <div class="mb-4 flex items-start justify-between">
                        <div class="rounded-lg p-2 {{ $tone }}">
                            <span class="material-symbols-outlined">{{ $icon }}</span>
                        </div>
                        <span class="rounded-full px-2 py-1 text-xs font-bold {{ $badgeTone }}">{{ $badge }}</span>
                    </div>
                    <p class="mb-1 text-sm text-on-surface-variant">{{ $label }}</p>
                    <h3 class="text-2xl font-extrabold text-primary">{{ $value }}</h3>
                    <p class="mt-2 text-[10px] text-outline">{{ $caption }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <section class="rounded-xl border border-outline-variant bg-white p-6 shadow-sm xl:col-span-2">
                <div class="mb-8 flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <h3 class="text-lg font-bold text-primary">توزيع الموظفين حسب الشركة</h3>
                        <p class="text-sm text-on-surface-variant">مقارنة عدد الموظفين بين شركات المجموعة</p>
                    </div>
                    <span class="rounded-full bg-surface-container-low px-3 py-1 text-xs font-bold text-primary">Live HR Data</span>
                </div>
                <div class="flex h-72 items-end justify-around gap-4 border-b border-outline-variant px-2 pb-4">
                    @foreach($companyBreakdown as $company)
                        <div class="group flex h-full min-w-0 flex-1 flex-col items-center justify-end gap-3">
                            <div class="relative flex h-full w-full max-w-20 items-end overflow-hidden rounded-t-lg bg-surface-container-high">
                                <div class="chart-bar-transition w-full bg-primary" style="height: {{ max($company['percentage'], 8) }}%"></div>
                                <div class="absolute inset-x-0 bottom-full mb-2 rounded bg-inverse-surface px-2 py-1 text-center text-xs text-inverse-on-surface opacity-0 transition-opacity group-hover:opacity-100">
                                    {{ $company['employees'] }}
                                </div>
                            </div>
                            <div class="text-center">
                                <p class="truncate text-xs font-bold text-on-surface">{{ $company['name_en'] }}</p>
                                <p class="truncate text-[10px] text-on-surface-variant">{{ $company['name'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <aside class="rounded-xl border border-outline-variant bg-white p-6 shadow-sm">
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-primary">الأقسام الأعلى كثافة</h3>
                        <p class="text-sm text-on-surface-variant">حسب عدد الموظفين</p>
                    </div>
                    <span class="material-symbols-outlined text-secondary">monitoring</span>
                </div>
                <div class="space-y-4">
                    @forelse($departmentBreakdown as $department)
                        @php($percentage = $metrics['headcount'] > 0 ? round(($department->total / $metrics['headcount']) * 100) : 0)
                        <div>
                            <div class="mb-2 flex justify-between text-sm">
                                <span class="font-semibold text-on-surface">{{ $department->name ?? 'غير محدد' }}</span>
                                <span class="font-bold text-primary">{{ $department->total }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-surface-container-highest">
                                <div class="h-full rounded-full bg-secondary" style="width: {{ max($percentage, 4) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-lg bg-surface-container-low p-4 text-sm text-on-surface-variant">لا توجد بيانات أقسام بعد.</div>
                    @endforelse
                </div>
            </aside>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <section class="overflow-hidden rounded-xl border border-outline-variant bg-white shadow-sm xl:col-span-2">
                <div class="flex items-center justify-between border-b border-outline-variant bg-surface-container-low p-6">
                    <h3 class="flex items-center gap-2 text-lg font-bold text-primary">
                        <span class="material-symbols-outlined">table_chart</span>
                        أحدث الموظفين
                    </h3>
                    <button class="rounded p-1 transition-colors hover:bg-surface-container">
                        <span class="material-symbols-outlined text-on-surface-variant">filter_list</span>
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-right text-sm">
                        <thead class="bg-surface-container-highest/30 text-on-surface-variant">
                            <tr>
                                <th class="border-b border-outline-variant px-6 py-4 font-bold">الموظف</th>
                                <th class="border-b border-outline-variant px-6 py-4 font-bold">الشركة</th>
                                <th class="border-b border-outline-variant px-6 py-4 font-bold">القسم</th>
                                <th class="border-b border-outline-variant px-6 py-4 font-bold">تاريخ بداية العقد</th>
                                <th class="border-b border-outline-variant px-6 py-4 font-bold">عرض</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/50">
                            @foreach($recentEmployees as $employee)
                                <tr class="transition-colors hover:bg-surface-container-high/20">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-fixed font-bold text-primary">{{ mb_substr($employee->name_ar, 0, 1) }}</div>
                                            <div>
                                                <div class="font-bold text-primary">{{ $employee->name_ar }}</div>
                                                <div class="text-[11px] text-on-surface-variant">#{{ $employee->employee_code }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">{{ $employee->company?->name_ar }}</td>
                                    <td class="px-6 py-4">{{ $employee->department?->name_ar ?? '-' }}</td>
                                    <td class="px-6 py-4">{{ $employee->contract_start_date?->format('Y-m-d') ?? '-' }}</td>
                                    <td class="px-6 py-4">
                                        <a href="{{ route('employees.show', $employee) }}" class="inline-flex rounded-full p-2 transition-colors hover:bg-surface-container">
                                            <span class="material-symbols-outlined text-primary">visibility</span>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <aside class="relative overflow-hidden rounded-xl border border-secondary-container/40 bg-gradient-to-br from-primary to-primary-container p-6 text-on-primary shadow-sm">
                <span class="material-symbols-outlined absolute -right-2 -top-2 text-8xl text-white/10">smart_toy</span>
                <div class="relative">
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-white/15">
                        <span class="material-symbols-outlined">auto_awesome</span>
                    </div>
                    <h3 class="text-xl font-bold">رؤى تنفيذية</h3>
                    <p class="mt-2 text-sm text-white/80">يعرض هذا التقرير مؤشرات مباشرة من قاعدة بيانات الموارد البشرية الحالية.</p>
                    <div class="mt-6 space-y-3">
                        <div class="rounded-lg bg-white/10 p-3 text-sm">نسبة التوطين الحالية {{ $metrics['saudization'] }}%.</div>
                        <div class="rounded-lg bg-white/10 p-3 text-sm">إجمالي الرواتب المسجلة {{ number_format($metrics['payroll'], 2) }} ر.س.</div>
                        <div class="rounded-lg bg-white/10 p-3 text-sm">يوجد {{ number_format($metrics['pendingLeaves']) }} طلب إجازة بانتظار الاعتماد.</div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
@endsection
