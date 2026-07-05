@extends('layouts.app')

@section('title', 'الموظفون')

@section('content')
    @php
        $selectedCompany = $selectedCompanyId ? $companies->firstWhere('id', (int) $selectedCompanyId) : null;
        $companyCardTones = [
            ['from' => '#1a2b4b', 'via' => '#243b63', 'to' => '#d4af37'],
            ['from' => '#0f1d33', 'via' => '#1a2b4b', 'to' => '#b68e17'],
            ['from' => '#243b63', 'via' => '#1a2b4b', 'to' => '#d4af37'],
            ['from' => '#1a2b4b', 'via' => '#334b73', 'to' => '#f4df96'],
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
                    <a href="{{ route('employees.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}" class="inline-flex items-center gap-2 rounded-xl border border-outline-variant/60 bg-white px-5 py-3 text-sm font-black text-primary shadow-sm transition hover:-translate-y-0.5 hover:bg-primary-fixed">
                        <span class="material-symbols-outlined text-lg">download</span>
                        <span>تصدير Excel</span>
                    </a>
                    <a href="{{ route('employees.import-template', array_filter(['company_id' => $selectedCompanyId, 'format' => 'xlsx'])) }}" class="inline-flex items-center gap-2 rounded-xl border border-outline-variant/60 bg-white px-5 py-3 text-sm font-black text-on-surface-variant shadow-sm transition hover:-translate-y-0.5 hover:bg-surface-container">
                        <span class="material-symbols-outlined text-lg">description</span>
                        <span>قالب الاستيراد</span>
                    </a>
                    <form method="POST" action="{{ route('employees.import') }}" enctype="multipart/form-data" class="flex max-w-full flex-wrap items-center gap-2 rounded-xl border border-outline-variant/60 bg-white p-1.5 shadow-sm">
                        @csrf
                        @if($selectedCompanyId)
                            <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                        @endif
                        <label class="cursor-pointer rounded-lg px-3 py-2 text-sm font-black text-on-surface-variant transition hover:bg-surface-container">
                            <input type="file" name="import_file" accept=".xlsx,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv" class="sr-only" onchange="this.closest('form').querySelector('[data-import-file-name]').textContent = this.files[0]?.name || 'لم يتم اختيار ملف'; this.closest('form').querySelector('[data-import-submit]').disabled = !this.files.length;">
                            <span class="inline-flex items-center gap-2">
                                <span class="material-symbols-outlined text-lg">upload_file</span>
                                <span>اختيار ملف</span>
                            </span>
                        </label>
                        <span data-import-file-name class="max-w-40 truncate px-1 text-xs font-bold text-on-surface-variant">XLSX أو CSV</span>
                        <button data-import-submit disabled class="rounded-lg bg-primary px-4 py-2 text-sm font-black text-white transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:bg-outline disabled:text-on-surface-variant">
                            استيراد
                        </button>
                    </form>
                    <a href="{{ route('employees.create') }}" class="stitch-btn-primary inline-flex items-center gap-2 px-6 py-3">
                        <span class="material-symbols-outlined">person_add</span>
                        <span>إضافة موظف جديد</span>
                    </a>
                @endcan
            </div>
        </section>

        @if(session('import_summary'))
            <section class="rounded-2xl border border-green-200 bg-green-50 p-4 text-green-900 shadow-sm">
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined rounded-xl bg-green-100 p-2 text-green-700">check_circle</span>
                    <div>
                        <h3 class="font-black">تم استيراد ملف الموظفين بنجاح</h3>
                        <p class="mt-1 text-sm">
                            تم إنشاء {{ number_format(session('import_summary.created')) }} موظف من ملف
                            <span class="font-bold">{{ session('import_summary.file') }}</span>.
                        </p>
                    </div>
                </div>
            </section>
        @endif

        @if($errors->has('import_file'))
            <section class="rounded-2xl border border-red-200 bg-red-50 p-4 text-red-900 shadow-sm">
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined rounded-xl bg-red-100 p-2 text-red-700">error</span>
                    <div class="min-w-0">
                        <h3 class="font-black">{{ $errors->first('import_file') }}</h3>
                        @if(session('import_errors'))
                            <ul class="mt-2 space-y-1 text-sm leading-6">
                                @foreach(session('import_errors') as $importError)
                                    <li>{{ $importError }}</li>
                                @endforeach
                            </ul>
                            @if((int) session('import_error_count') > count(session('import_errors')))
                                <p class="mt-2 text-xs font-bold">تم عرض أول {{ count(session('import_errors')) }} أخطاء من أصل {{ session('import_error_count') }}.</p>
                            @endif
                        @else
                            <p class="mt-1 text-sm">راجع صيغة الملف والحقول المطلوبة ثم حاول مرة أخرى.</p>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        @if(! $selectedCompanyId)
            <section class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                @foreach($companies as $index => $company)
                    @php
                        $tone = $companyCardTones[$index % count($companyCardTones)];
                        $companyUrl = route('employees.index', ['company_id' => $company->id]);
                    @endphp

                    <a href="{{ $companyUrl }}"
                       class="group app-card overflow-hidden transition duration-200 hover:-translate-y-1 hover:shadow-[0_22px_48px_rgba(26,43,75,0.12)] focus:outline-none focus:ring-4 focus:ring-secondary/20">
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
                    <div class="app-kpi-card overflow-hidden p-0">
                        <div class="flex items-center justify-between gap-4 p-4">
                            <div>
                                <p class="text-xs font-bold text-on-surface-variant">{{ $metric['label'] }}</p>
                                <strong class="mt-1 block font-tabular text-2xl font-black text-on-surface">{{ number_format($metric['value']) }}</strong>
                            </div>
                            <span class="material-symbols-outlined rounded-2xl p-3 text-xl {{ $metric['tone'] }}">{{ $metric['icon'] }}</span>
                        </div>
                    </div>
                @endforeach
            </section>

            <div class="space-y-5">
                <section class="app-card overflow-hidden p-0">
                    <div class="flex flex-col gap-4 border-b border-outline-variant/50 bg-gradient-to-l from-primary-fixed/70 to-white p-5 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h3 class="text-lg font-black text-on-surface">تصفية النتائج</h3>
                            <p class="mt-1 text-xs text-on-surface-variant">{{ $filterCount }} فلتر نشط</p>
                        </div>
                        <a href="{{ route('employees.index', ['company_id' => $selectedCompanyId]) }}" class="inline-flex w-fit items-center justify-center rounded-full bg-white/85 px-4 py-2 text-xs font-black text-primary shadow-sm transition hover:bg-white">إعادة ضبط</a>
                    </div>

                    <form class="grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)_minmax(220px,1.35fr)_150px] lg:items-end">
                        <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                        <div>
                            <label class="mb-2 block text-xs font-black text-on-surface">الفرع</label>
                            <select name="branch_id" class="stitch-input w-full rounded-xl px-3 py-3 text-sm">
                                <option value="">الكل</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected($filters['branch_id'] === $branch->id)>{{ $branch->name_ar }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-black text-on-surface">القسم</label>
                            <select name="department_id" class="stitch-input w-full rounded-xl px-3 py-3 text-sm">
                                <option value="">الكل</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}" @selected($filters['department_id'] === $department->id)>{{ $department->name_ar }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-black text-on-surface">الحالة</label>
                            <select name="status" class="stitch-input w-full rounded-xl px-3 py-3 text-sm">
                                <option value="">الكل</option>
                                @foreach(\App\Models\Employee::STATUSES as $statusOption)
                                    <option value="{{ $statusOption }}" @selected($filters['status'] === $statusOption)>{{ \App\Models\Employee::STATUS_LABELS_AR[$statusOption] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-black text-on-surface">نوع العقد</label>
                            <select name="contract_type" class="stitch-input w-full rounded-xl px-3 py-3 text-sm">
                                <option value="">الكل</option>
                                @foreach(\App\Models\Employee::CONTRACT_TYPES as $contractTypeOption)
                                    <option value="{{ $contractTypeOption }}" @selected($filters['contract_type'] === $contractTypeOption)>{{ \App\Models\Employee::CONTRACT_TYPE_LABELS_AR[$contractTypeOption] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-black text-on-surface">بحث</label>
                            <div class="relative">
                                <input name="search" value="{{ $search }}" class="stitch-input w-full rounded-xl px-3 py-3 pe-10 text-sm" placeholder="اسم، رقم وظيفي، هوية">
                                <span class="material-symbols-outlined absolute end-3 top-1/2 -translate-y-1/2 text-base text-on-surface-variant">search</span>
                            </div>
                        </div>
                        <button class="stitch-btn-primary w-full px-4 py-3 shadow-[0_12px_28px_rgba(212,175,55,0.22)]">تطبيق الفلتر</button>
                    </form>
                </section>

                <section class="min-w-0">
                    <div class="app-card overflow-hidden shadow-[0_18px_48px_rgba(26,43,75,0.08)]">
                        <div class="flex flex-col justify-between gap-3 border-b border-outline-variant/50 bg-white p-5 sm:flex-row sm:items-center">
                            <div>
                                <h3 class="text-xl font-black text-on-surface">قائمة الموظفين</h3>
                                <p class="mt-1 text-xs font-bold text-on-surface-variant">{{ number_format($employees->total()) }} نتيجة مطابقة</p>
                            </div>
                            <span class="inline-flex items-center gap-2 rounded-full bg-primary-fixed px-4 py-2 text-xs font-black text-primary">
                                <span class="material-symbols-outlined text-base">table_chart</span>
                                عرض جدولي
                            </span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="app-table min-w-[1040px] text-start text-sm">
                                <thead>
                                    <tr>
                                        <th class="px-5 py-4 font-label text-xs font-black uppercase tracking-wider">الموظف</th>
                                        <th class="px-5 py-4 font-label text-xs font-black uppercase tracking-wider">توظف في</th>
                                        <th class="px-5 py-4 font-label text-xs font-black uppercase tracking-wider">المسمى الوظيفي</th>
                                        <th class="px-5 py-4 font-label text-xs font-black uppercase tracking-wider">القسم</th>
                                        <th class="px-5 py-4 font-label text-xs font-black uppercase tracking-wider">التصنيف</th>
                                        <th class="px-5 py-4 font-label text-xs font-black uppercase tracking-wider">اكتمال الملف</th>
                                        <th class="px-5 py-4 font-label text-xs font-black uppercase tracking-wider">الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-outline-variant/45 bg-white">
                                    @forelse($employees as $employee)
                                        @php $percent = $employee->profile_completion_percent; @endphp
                                        <tr class="transition hover:bg-primary-fixed/20">
                                            <td class="px-5 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-primary-fixed text-sm font-black text-primary ring-1 ring-primary/10">
                                                        {{ mb_substr($employee->name_ar, 0, 1) }}
                                                    </div>
                                                    <div class="min-w-0">
                                                        <a href="{{ route('employees.show', $employee) }}" class="block max-w-52 truncate font-black text-primary hover:underline">{{ $employee->name_ar }}</a>
                                                        <p class="mt-1 max-w-64 truncate text-xs text-on-surface-variant">
                                                            <span class="font-tabular">{{ $employee->employee_code }}</span>
                                                            <span class="mx-1">&middot;</span>
                                                            {{ $employee->company?->name_ar }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-5 py-4">
                                                <p class="font-tabular text-sm font-bold text-on-surface">{{ $employee->contract_start_date?->format('Y-m-d') ?? '-' }}</p>
                                                <p class="mt-0.5 text-xs text-on-surface-variant">{{ $employee->branch?->name_ar ?? '-' }}</p>
                                            </td>
                                            <td class="px-5 py-4">
                                                <span class="inline-flex max-w-32 items-center justify-center rounded-full bg-surface-container px-3 py-2 text-center text-xs font-black leading-4 text-on-surface">{{ $employee->position?->title_ar ?? '-' }}</span>
                                            </td>
                                            <td class="px-5 py-4">
                                                <span class="text-sm font-bold text-on-surface-variant">{{ $employee->department?->name_ar ?? '-' }}</span>
                                            </td>
                                            <td class="px-5 py-4">
                                                <span class="app-status-chip whitespace-nowrap {{ $employee->saudi_non_saudi === 'saudi' ? 'bg-green-100 text-green-700' : 'bg-secondary-fixed text-secondary' }}">
                                                    <span class="h-2 w-2 rounded-full {{ $employee->saudi_non_saudi === 'saudi' ? 'bg-green-600' : 'bg-secondary' }}"></span>
                                                    {{ $employee->saudi_non_saudi === 'saudi' ? 'سعودي' : 'مقيم' }}
                                                </span>
                                            </td>
                                            <td class="px-5 py-4">
                                                <div class="w-36" title="{{ $percent }}% من بيانات الملف مكتملة">
                                                    <div class="mb-2 flex items-center justify-between gap-3">
                                                        <span class="text-xs font-black text-on-surface-variant">اكتمال</span>
                                                        <span class="font-tabular text-sm font-black text-primary">{{ $percent }}%</span>
                                                    </div>
                                                    <div class="h-2.5 overflow-hidden rounded-full bg-surface-container">
                                                        <div class="h-full rounded-full bg-primary transition-all" style="width: {{ $percent }}%"></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-5 py-4">
                                                <a href="{{ route('employees.show', $employee) }}" class="inline-flex items-center gap-1 rounded-xl bg-primary-fixed px-3 py-2 text-sm font-black text-primary transition hover:bg-primary hover:text-white">
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
