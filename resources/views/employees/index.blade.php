@extends('layouts.app')

@section('title', 'الموظفون')

@section('content')
    @php
        $selectedCompany = $selectedCompanyId ? $companies->firstWhere('id', (int) $selectedCompanyId) : null;
        $companyCardTones = [
            ['from' => '#170040', 'via' => '#2e1065', 'to' => '#6b38d4'],
            ['from' => '#24002b', 'via' => '#460051', 'to' => '#dc49f2'],
            ['from' => '#1e1038', 'via' => '#503788', 'to' => '#8455ef'],
            ['from' => '#2d3133', 'via' => '#2e1065', 'to' => '#6b38d4'],
        ];

        $companyLogo = function ($company) {
            $name = mb_strtolower($company->name_en.' '.$company->name_ar);

            return match (true) {
                str_contains($name, 'factory') || str_contains($name, 'مصنع') => 'images/companies/amniat-factory.png',
                str_contains($name, 'construction') || str_contains($name, 'مقاولات') || str_contains($name, 'المقاولات') => 'images/companies/ptc-construction.png',
                str_contains($name, 'ptc') || str_contains($name, 'تجارة') || str_contains($name, 'التجارة') => 'images/companies/ptc.png',
                default => 'images/companies/amniat.png',
            };
        };

        $filterCount = collect($filters)->filter()->count() + ($search !== '' ? 1 : 0);
    @endphp

    <div class="mx-auto max-w-7xl space-y-6">
        <section class="flex flex-col justify-between gap-4 rounded-3xl border border-outline-variant/50 bg-white p-6 shadow-[0_16px_38px_rgba(25,28,30,0.05)] md:flex-row md:items-end">
            <div>
                <p class="font-label text-xs font-bold uppercase tracking-[0.18em] text-secondary">People Directory</p>
                <h2 class="mt-2 font-headline text-3xl font-black text-on-surface">
                    {{ $selectedCompanyId ? 'موظفو '.($selectedCompany?->name_ar ?? 'الشركة') : 'دليل الموظفين' }}
                </h2>
                <p class="mt-2 max-w-2xl text-sm leading-7 text-on-surface-variant">
                    {{ $selectedCompanyId ? 'عرض وإدارة ملفات الموظفين الخاصة بهذه الشركة فقط.' : 'اختر شركة لعرض موظفيها وإدارة ملفاتهم حسب الصلاحيات الحالية.' }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                @if($selectedCompanyId)
                    <a href="{{ route('employees.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-outline-variant/60 bg-white px-5 py-3 text-sm font-black text-primary shadow-sm transition hover:-translate-y-0.5 hover:bg-primary-fixed">
                        <span class="material-symbols-outlined text-lg">apps</span>
                        <span>كل الشركات</span>
                    </a>
                @endif
                @can('manage-employees')
                    <a href="{{ route('employees.create') }}" class="stitch-btn-primary inline-flex items-center gap-2 px-6 py-3">
                        <span class="material-symbols-outlined">person_add</span>
                        <span>إضافة موظف جديد</span>
                    </a>
                @endcan
            </div>
        </section>

        @if(! $selectedCompanyId)
            <section class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                @foreach($companies as $index => $company)
                    @php
                        $tone = $companyCardTones[$index % count($companyCardTones)];
                        $companyUrl = route('employees.index', ['company_id' => $company->id]);
                    @endphp

                    <a href="{{ $companyUrl }}"
                       class="group app-card overflow-hidden transition duration-200 hover:-translate-y-1 hover:shadow-[0_22px_48px_rgba(46,16,101,0.12)] focus:outline-none focus:ring-4 focus:ring-secondary/20">
                        <div class="h-2" style="background: linear-gradient(to left, {{ $tone['from'] }}, {{ $tone['via'] }}, {{ $tone['to'] }});"></div>
                        <div class="p-5">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <span class="inline-flex items-center gap-2 rounded-full bg-primary-fixed px-3 py-1 text-[11px] font-black text-primary">
                                        <span class="material-symbols-outlined text-sm">corporate_fare</span>
                                        عرض موظفي الشركة
                                    </span>
                                    <h3 class="mt-4 truncate text-xl font-black text-on-surface">{{ $company->name_ar }}</h3>
                                    <p class="mt-1 truncate text-xs font-bold uppercase tracking-wide text-on-surface-variant">{{ $company->name_en }}</p>
                                </div>

                                <div class="flex h-16 w-20 shrink-0 items-center justify-center rounded-2xl border border-outline-variant/45 bg-white p-3 shadow-[0_12px_26px_rgba(25,28,30,0.06)]">
                                    <img src="{{ asset($companyLogo($company)) }}" alt="{{ $company->name_ar }}" class="max-h-11 max-w-full object-contain">
                                </div>
                            </div>

                            <div class="mt-8 flex items-center justify-between rounded-2xl bg-surface-container-low px-4 py-3">
                                <span class="text-xs font-bold text-on-surface-variant">عدد الموظفين</span>
                                <strong class="font-tabular text-3xl font-black text-primary">{{ number_format($company->employees_count) }}</strong>
                            </div>
                        </div>
                    </a>
                @endforeach
            </section>
        @else
            <section class="grid gap-4 sm:grid-cols-3">
                @foreach([
                    ['label' => 'تحت التجربة', 'value' => $dataQuality['probation'], 'icon' => 'hourglass_top', 'tone' => 'bg-yellow-100 text-yellow-800'],
                    ['label' => 'إقامات منتهية', 'value' => $dataQuality['expired_iqama'], 'icon' => 'badge', 'tone' => 'bg-red-100 text-red-700'],
                    ['label' => 'ملفات غير مكتملة', 'value' => $dataQuality['incomplete'], 'icon' => 'fact_check', 'tone' => 'bg-primary-fixed text-primary'],
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

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-[320px_minmax(0,1fr)]">
                <aside class="app-card h-fit p-5">
                    <div class="mb-5 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-black text-on-surface">تصفية النتائج</h3>
                            <p class="mt-1 text-xs text-on-surface-variant">{{ $filterCount }} فلتر نشط</p>
                        </div>
                        <a href="{{ route('employees.index', ['company_id' => $selectedCompanyId]) }}" class="text-xs font-bold text-primary hover:underline">إعادة ضبط</a>
                    </div>

                    <form class="space-y-4">
                        <div>
                            <label class="mb-2 block font-label text-xs font-bold uppercase tracking-wider text-on-surface-variant">الشركة</label>
                            <select name="company_id" class="stitch-input w-full px-3 py-2.5 text-sm">
                                <option value="">الكل</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}" @selected((int) $selectedCompanyId === $company->id)>{{ $company->name_en }} - {{ $company->name_ar }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block font-label text-xs font-bold uppercase tracking-wider text-on-surface-variant">الفرع</label>
                            <select name="branch_id" class="stitch-input w-full px-3 py-2.5 text-sm">
                                <option value="">الكل</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected($filters['branch_id'] === $branch->id)>{{ $branch->name_ar }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block font-label text-xs font-bold uppercase tracking-wider text-on-surface-variant">القسم</label>
                            <select name="department_id" class="stitch-input w-full px-3 py-2.5 text-sm">
                                <option value="">الكل</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}" @selected($filters['department_id'] === $department->id)>{{ $department->name_ar }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block font-label text-xs font-bold uppercase tracking-wider text-on-surface-variant">الحالة</label>
                            <select name="status" class="stitch-input w-full px-3 py-2.5 text-sm">
                                <option value="">الكل</option>
                                @foreach(\App\Models\Employee::STATUSES as $statusOption)
                                    <option value="{{ $statusOption }}" @selected($filters['status'] === $statusOption)>{{ \App\Models\Employee::STATUS_LABELS_AR[$statusOption] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block font-label text-xs font-bold uppercase tracking-wider text-on-surface-variant">نوع العقد</label>
                            <select name="contract_type" class="stitch-input w-full px-3 py-2.5 text-sm">
                                <option value="">الكل</option>
                                @foreach(\App\Models\Employee::CONTRACT_TYPES as $contractTypeOption)
                                    <option value="{{ $contractTypeOption }}" @selected($filters['contract_type'] === $contractTypeOption)>{{ \App\Models\Employee::CONTRACT_TYPE_LABELS_AR[$contractTypeOption] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block font-label text-xs font-bold uppercase tracking-wider text-on-surface-variant">بحث</label>
                            <div class="relative">
                                <input name="search" value="{{ $search }}" class="stitch-input w-full px-3 py-2.5 pe-10 text-sm" placeholder="اسم، رقم وظيفي، هوية">
                                <span class="material-symbols-outlined absolute end-3 top-1/2 -translate-y-1/2 text-base text-on-surface-variant">search</span>
                            </div>
                        </div>
                        <button class="stitch-btn-primary w-full px-4 py-3">تطبيق الفلتر</button>
                    </form>
                </aside>

                <section class="min-w-0">
                    <div class="app-card overflow-hidden">
                        <div class="flex flex-col justify-between gap-3 border-b border-outline-variant/50 bg-white p-5 sm:flex-row sm:items-center">
                            <div>
                                <h3 class="text-lg font-black text-on-surface">قائمة الموظفين</h3>
                                <p class="mt-1 text-xs text-on-surface-variant">{{ number_format($employees->total()) }} نتيجة مطابقة</p>
                            </div>
                            <span class="inline-flex items-center gap-2 rounded-full bg-surface-container-low px-3 py-1.5 text-xs font-bold text-on-surface-variant">
                                <span class="material-symbols-outlined text-base">table_chart</span>
                                عرض جدولي
                            </span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="app-table min-w-[980px] text-start text-sm">
                                <thead>
                                    <tr>
                                        <th class="px-6 py-4 font-label text-xs font-bold uppercase tracking-wider">الموظف</th>
                                        <th class="px-6 py-4 font-label text-xs font-bold uppercase tracking-wider">توظف في</th>
                                        <th class="px-6 py-4 font-label text-xs font-bold uppercase tracking-wider">المسمى الوظيفي</th>
                                        <th class="px-6 py-4 font-label text-xs font-bold uppercase tracking-wider">القسم</th>
                                        <th class="px-6 py-4 font-label text-xs font-bold uppercase tracking-wider">التصنيف</th>
                                        <th class="px-6 py-4 font-label text-xs font-bold uppercase tracking-wider">اكتمال الملف</th>
                                        <th class="px-6 py-4 font-label text-xs font-bold uppercase tracking-wider">الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-outline-variant/35">
                                    @forelse($employees as $employee)
                                        @php $percent = $employee->profile_completion_percent; @endphp
                                        <tr class="transition hover:bg-surface-container-low">
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="flex h-11 w-11 items-center justify-center rounded-full bg-primary-fixed text-sm font-black text-primary ring-1 ring-primary/10">
                                                        {{ mb_substr($employee->name_ar, 0, 1) }}
                                                    </div>
                                                    <div class="min-w-0">
                                                        <a href="{{ route('employees.show', $employee) }}" class="block truncate font-black text-primary hover:underline">{{ $employee->name_ar }}</a>
                                                        <p class="mt-0.5 truncate text-xs text-on-surface-variant">
                                                            <span class="font-tabular">{{ $employee->employee_code }}</span>
                                                            <span class="mx-1">&middot;</span>
                                                            {{ $employee->company?->name_ar }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <p class="font-tabular text-sm text-on-surface">{{ $employee->contract_start_date?->format('Y-m-d') ?? '-' }}</p>
                                                <p class="mt-0.5 text-xs text-on-surface-variant">{{ $employee->branch?->name_ar ?? '-' }}</p>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="app-status-chip bg-surface-container text-on-surface">{{ $employee->position?->title_ar ?? '-' }}</span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="text-sm text-on-surface-variant">{{ $employee->department?->name_ar ?? '-' }}</span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="app-status-chip {{ $employee->saudi_non_saudi === 'saudi' ? 'bg-green-100 text-green-700' : 'bg-secondary-fixed text-secondary' }}">
                                                    <span class="h-2 w-2 rounded-full {{ $employee->saudi_non_saudi === 'saudi' ? 'bg-green-600' : 'bg-secondary' }}"></span>
                                                    {{ $employee->saudi_non_saudi === 'saudi' ? 'سعودي' : 'مقيم' }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-3" title="{{ $percent }}% من بيانات الملف مكتملة">
                                                    <div class="relative flex h-10 w-10 items-center justify-center rounded-full"
                                                         style="background: conic-gradient(var(--color-primary) {{ $percent * 3.6 }}deg, var(--color-surface-container) 0deg)">
                                                        <div class="flex h-7 w-7 items-center justify-center rounded-full bg-white font-tabular text-[10px] font-black text-primary">
                                                            {{ $percent }}
                                                        </div>
                                                    </div>
                                                    <div class="hidden min-w-24 sm:block">
                                                        <div class="h-2 overflow-hidden rounded-full bg-surface-container">
                                                            <div class="h-full rounded-full bg-primary" style="width: {{ $percent }}%"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <a href="{{ route('employees.show', $employee) }}" class="inline-flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-bold text-primary transition hover:bg-primary-fixed">
                                                    <span class="material-symbols-outlined text-base">visibility</span>
                                                    <span>عرض</span>
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-6 py-14 text-center">
                                                <div class="mx-auto max-w-sm">
                                                    <span class="material-symbols-outlined text-4xl text-on-surface-variant">person_search</span>
                                                    <p class="mt-3 font-bold text-on-surface">لا توجد نتائج</p>
                                                    <p class="mt-1 text-sm text-on-surface-variant">جرّب تغيير الفلاتر أو البحث باسم آخر.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="border-t border-outline-variant/50 p-4">{{ $employees->links() }}</div>
                    </div>
                </section>
            </div>
        @endif
    </div>
@endsection
