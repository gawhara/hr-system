@extends('layouts.app')

@section('title', 'الموظفون')

@section('content')
    <div class="space-y-8">
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
            <div>
                <p class="font-label text-xs font-bold uppercase tracking-[0.25em] text-tertiary">People directory</p>
                <h2 class="mt-2 font-headline text-display-lg font-bold text-primary">دليل الموظفين</h2>
                <p class="mt-2 text-on-surface-variant">إدارة بيانات الموظفين عبر جميع فروع وشركات المجموعة.</p>
            </div>
            @can('manage-employees')
                <a href="{{ route('employees.create') }}" class="stitch-btn-primary flex items-center gap-2 px-6 py-3">
                    <span class="material-symbols-outlined">person_add</span>
                    <span>إضافة موظف جديد</span>
                </a>
            @endcan
        </div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-4">
            @foreach([
                ['إجمالي الموظفين', $companies->sum('employees_count'), 'badge'],
                ['تحت التجربة', $dataQuality['probation'], 'hourglass_top'],
                ['إقامات منتهية', $dataQuality['expired_iqama'], 'gpp_bad'],
                ['ملفات غير مكتملة', $dataQuality['incomplete'], 'rule'],
            ] as [$label, $value, $icon])
                <div class="rounded-2xl bg-surface-container-lowest p-6 shadow-[0_18px_36px_rgba(27,28,29,0.035)]">
                    <div class="mb-6 flex h-12 w-12 items-center justify-center rounded-xl bg-primary-fixed text-primary">
                        <span class="material-symbols-outlined">{{ $icon }}</span>
                    </div>
                    <p class="text-sm text-on-surface-variant">{{ $label }}</p>
                    <h4 class="mt-1 font-headline text-3xl font-bold text-primary">{{ number_format($value) }}</h4>
                </div>
            @endforeach
        </div>

        <div class="flex flex-col gap-6 xl:flex-row">
            <aside class="rounded-2xl bg-surface-container-lowest p-6 shadow-[0_18px_36px_rgba(27,28,29,0.035)] xl:w-80 xl:shrink-0">
                <div class="mb-6 flex items-center justify-between">
                    <h3 class="font-headline text-xl font-bold text-primary">تصفية النتائج</h3>
                    <a href="{{ route('employees.index') }}" class="text-xs font-bold text-primary hover:underline">إعادة ضبط</a>
                </div>
                <form class="space-y-5">
                    <div>
                        <label class="mb-2 block font-label text-xs font-bold uppercase tracking-wider text-on-surface-variant">الشركة</label>
                        <select name="company_id" class="stitch-input w-full px-3 py-2 text-sm">
                            <option value="">الكل</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" @selected((int) $selectedCompanyId === $company->id)>{{ $company->name_en }} - {{ $company->name_ar }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block font-label text-xs font-bold uppercase tracking-wider text-on-surface-variant">الفرع</label>
                        <select name="branch_id" class="stitch-input w-full px-3 py-2 text-sm">
                            <option value="">الكل</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected($filters['branch_id'] === $branch->id)>{{ $branch->name_ar }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block font-label text-xs font-bold uppercase tracking-wider text-on-surface-variant">القسم</label>
                        <select name="department_id" class="stitch-input w-full px-3 py-2 text-sm">
                            <option value="">الكل</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" @selected($filters['department_id'] === $department->id)>{{ $department->name_ar }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block font-label text-xs font-bold uppercase tracking-wider text-on-surface-variant">الحالة</label>
                        <select name="status" class="stitch-input w-full px-3 py-2 text-sm">
                            <option value="">الكل</option>
                            @foreach(\App\Models\Employee::STATUSES as $statusOption)
                                <option value="{{ $statusOption }}" @selected($filters['status'] === $statusOption)>{{ \App\Models\Employee::STATUS_LABELS_AR[$statusOption] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block font-label text-xs font-bold uppercase tracking-wider text-on-surface-variant">نوع العقد</label>
                        <select name="contract_type" class="stitch-input w-full px-3 py-2 text-sm">
                            <option value="">الكل</option>
                            @foreach(\App\Models\Employee::CONTRACT_TYPES as $contractTypeOption)
                                <option value="{{ $contractTypeOption }}" @selected($filters['contract_type'] === $contractTypeOption)>{{ \App\Models\Employee::CONTRACT_TYPE_LABELS_AR[$contractTypeOption] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block font-label text-xs font-bold uppercase tracking-wider text-on-surface-variant">بحث</label>
                        <input name="search" value="{{ $search }}" class="stitch-input w-full px-3 py-2 text-sm" placeholder="اسم، رقم وظيفي، هوية">
                    </div>
                    <button class="stitch-btn-primary w-full px-4 py-3">تطبيق الفلتر</button>
                </form>
            </aside>

            <section class="min-w-0 flex-1">
                <div class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-[0_18px_36px_rgba(27,28,29,0.035)]">
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse text-right">
                            <thead>
                                <tr class="bg-surface-container-low">
                                    <th class="px-6 py-4 font-label text-xs font-bold uppercase tracking-wider text-on-surface-variant">الموظف</th>
                                    <th class="px-6 py-4 font-label text-xs font-bold uppercase tracking-wider text-on-surface-variant">توظف في</th>
                                    <th class="px-6 py-4 font-label text-xs font-bold uppercase tracking-wider text-on-surface-variant">المسمى الوظيفي</th>
                                    <th class="px-6 py-4 font-label text-xs font-bold uppercase tracking-wider text-on-surface-variant">القسم</th>
                                    <th class="px-6 py-4 font-label text-xs font-bold uppercase tracking-wider text-on-surface-variant">الحالة</th>
                                    <th class="px-6 py-4 font-label text-xs font-bold uppercase tracking-wider text-on-surface-variant">اكتمال الملف</th>
                                    <th class="px-6 py-4 font-label text-xs font-bold uppercase tracking-wider text-on-surface-variant">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/70">
                                @forelse($employees as $employee)
                                    <tr class="transition hover:bg-surface-container-high">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-fixed text-sm font-black text-primary">
                                                    {{ mb_substr($employee->name_ar, 0, 1) }}
                                                </div>
                                                <div>
                                                    <a href="{{ route('employees.show', $employee) }}" class="font-bold text-primary hover:underline">{{ $employee->name_ar }}</a>
                                                    <p class="text-xs text-on-surface-variant">{{ $employee->employee_code }} &middot; {{ $employee->company?->name_ar }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <p class="text-sm text-on-surface">{{ $employee->contract_start_date?->format('Y-m-d') ?? '-' }}</p>
                                            <p class="text-xs text-on-surface-variant">{{ $employee->branch?->name_ar ?? '-' }}</p>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="rounded-full bg-surface-container px-3 py-1 text-xs font-medium">{{ $employee->position?->title_ar ?? '-' }}</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-sm text-on-surface-variant">{{ $employee->department?->name_ar ?? '-' }}</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center gap-2 text-xs font-medium">
                                                <span class="h-2 w-2 rounded-full bg-tertiary"></span>
                                                {{ $employee->saudi_non_saudi === 'saudi' ? 'سعودي' : 'مقيم' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            @php $percent = $employee->profile_completion_percent; @endphp
                                            <div class="flex items-center gap-2" title="{{ $percent }}% من بيانات الملف مكتملة">
                                                <div class="relative flex h-9 w-9 items-center justify-center rounded-full"
                                                     style="background: conic-gradient(var(--color-primary) {{ $percent * 3.6 }}deg, var(--color-surface-container) 0deg)">
                                                    <div class="flex h-6 w-6 items-center justify-center rounded-full bg-surface-container-lowest text-[9px] font-black text-primary">
                                                        {{ $percent }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <a href="{{ route('employees.show', $employee) }}" class="rounded-lg px-3 py-2 text-primary transition hover:bg-surface-container">عرض</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="px-6 py-10 text-center text-on-surface-variant">لا توجد نتائج</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="p-4">{{ $employees->links() }}</div>
                </div>
            </section>
        </div>
    </div>
@endsection
