@extends('layouts.app')

@section('title', $employee->name_ar)

@section('content')
    <div class="mx-auto max-w-7xl space-y-6">
        @if(session('status'))
            <div class="rounded-2xl border border-green-300 bg-green-50 p-4 text-sm font-bold text-green-800">{{ session('status') }}</div>
        @endif

        <div class="overflow-hidden rounded-3xl border border-outline-variant/50 bg-white shadow-[0_18px_44px_rgba(25,28,30,0.06)]">
            <div class="relative overflow-hidden bg-gradient-to-br from-[#170040] via-[#2e1065] to-[#6b38d4] p-6 text-white sm:p-8">
                <div class="absolute -top-24 end-16 h-64 w-64 rounded-full bg-white/10 blur-3xl"></div>
                <div class="absolute -bottom-28 start-8 h-72 w-72 rounded-full bg-[#dc49f2]/18 blur-3xl"></div>
                <div class="relative flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-center gap-5">
                        <div class="flex h-24 w-24 shrink-0 items-center justify-center rounded-3xl border border-white/18 bg-white/12 text-4xl font-black ring-1 ring-white/12">
                            {{ mb_substr($employee->name_ar, 0, 1) }}
                        </div>
                        <div>
                            <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-bold text-white/78 backdrop-blur">
                                <span class="material-symbols-outlined text-base">person</span>
                                ملف موظف
                            </span>
                            <h2 class="mt-3 text-3xl font-black sm:text-4xl">{{ $employee->name_ar }}</h2>
                            <p class="mt-2 text-sm text-white/72">{{ $employee->position?->title_ar }} - <span class="font-tabular">{{ $employee->employee_code }}</span></p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        @can('manage-employees')
                            <a href="{{ route('employees.edit', $employee) }}" class="flex items-center gap-2 rounded-xl bg-white/12 px-4 py-2 text-sm font-bold ring-1 ring-white/18 transition hover:bg-white/20">
                                <span class="material-symbols-outlined text-base">edit</span>
                                <span>تعديل</span>
                            </a>
                            <details class="relative">
                                <summary class="flex cursor-pointer list-none items-center gap-2 rounded-xl bg-white/12 px-4 py-2 text-sm font-bold ring-1 ring-white/18 transition hover:bg-white/20">
                                    <span class="material-symbols-outlined text-base">swap_horiz</span>
                                    <span>تغيير الحالة</span>
                                </summary>
                                <form method="POST" action="{{ route('employees.status', $employee) }}"
                                      class="absolute start-0 top-12 z-50 w-80 space-y-3 rounded-2xl border border-outline-variant/40 bg-white p-4 text-on-surface shadow-2xl"
                                      onsubmit="return confirm('تأكيد تغيير حالة الموظف؟ يُسجل التغيير في سجل الحالات وسجل التدقيق.');">
                                    @csrf
                                    <label class="block text-xs font-bold text-on-surface-variant">الحالة الجديدة</label>
                                    <select name="status" class="stitch-input w-full px-3 py-2 text-sm">
                                        @foreach(\App\Models\Employee::STATUSES as $statusOption)
                                            @continue($statusOption === $employee->status)
                                            <option value="{{ $statusOption }}">{{ \App\Models\Employee::STATUS_LABELS_AR[$statusOption] }}</option>
                                        @endforeach
                                    </select>
                                    <label class="block text-xs font-bold text-on-surface-variant">السبب (إلزامي عند الإيقاف أو الإنهاء)</label>
                                    <textarea name="reason" class="stitch-input h-20 w-full px-3 py-2 text-sm"></textarea>
                                    <button class="stitch-btn-primary w-full py-2 text-sm">حفظ الحالة</button>
                                </form>
                            </details>
                        @endcan
                        <span class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2 text-sm font-black text-primary shadow-[0_14px_28px_rgba(23,0,64,0.18)]">
                            <span class="h-2 w-2 rounded-full bg-green-500"></span>
                            {{ \App\Models\Employee::STATUS_LABELS_AR[$employee->status] ?? $employee->status }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        @php
            $profileSummary = $employee->profileCompletion();
        @endphp

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach([
                ['label' => 'اكتمال الملف', 'value' => $profileSummary['percent'].'%', 'icon' => 'fact_check'],
                ['label' => 'الشركة', 'value' => $employee->company?->name_ar ?? '-', 'icon' => 'corporate_fare'],
                ['label' => 'القسم', 'value' => $employee->department?->name_ar ?? '-', 'icon' => 'groups'],
                ['label' => 'المدير المباشر', 'value' => $employee->manager?->name_ar ?? '-', 'icon' => 'supervisor_account'],
            ] as $metric)
                <div class="app-kpi-card p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-on-surface-variant">{{ $metric['label'] }}</p>
                            <strong class="mt-2 block truncate text-xl font-black text-on-surface">{{ $metric['value'] }}</strong>
                        </div>
                        <span class="material-symbols-outlined rounded-xl bg-primary-fixed p-2 text-primary">{{ $metric['icon'] }}</span>
                    </div>
                </div>
            @endforeach
        </section>

        @php
            $tabs = [
                'about' => ['نظرة عامة', 'person'],
                'work' => ['العمل والعقود', 'work'],
                'documents' => ['المستندات', 'description'],
                'leave' => ['الإجازات', 'event_busy'],
                'attendance' => ['الحضور والانصراف', 'timer'],
            ];
            if ($canSeePayroll) {
                $tabs['payroll'] = ['كشف الرواتب', 'payments'];
            }
            if ($canSeeActivity) {
                $tabs['activity'] = ['سجل النشاط', 'history'];
            }
        @endphp

        <div class="app-card sticky top-[88px] z-30 p-2" data-tabs>
            <nav class="flex gap-1 overflow-x-auto">
                @foreach($tabs as $key => [$label, $icon])
                    <button type="button" data-tab-trigger="{{ $key }}"
                            class="tab-trigger flex shrink-0 items-center gap-2 rounded-xl px-4 py-3 text-sm font-bold text-on-surface-variant transition hover:bg-surface-container-low">
                        <span class="material-symbols-outlined text-lg">{{ $icon }}</span>
                        <span>{{ $label }}</span>
                    </button>
                @endforeach
            </nav>
        </div>

        <div data-tab-panel="about" class="tab-panel space-y-6">
            @php
                $completion = $employee->profileCompletion();
                $money = fn ($value) => $value === null || $value === '' ? null : number_format((float) $value, 2);
            @endphp
            @if($completion['missing'] !== [] && auth()->user()->can('manage-employees'))
                <div class="rounded-2xl border border-yellow-300 bg-yellow-50 p-4 text-sm">
                    <p class="font-bold text-yellow-900">
                        اكتمال الملف: {{ $completion['percent'] }}%
                        — بيانات ناقصة ({{ count($completion['missing']) }}):
                    </p>
                    <p class="mt-1 text-yellow-800">{{ implode('، ', $completion['missing']) }}</p>
                </div>
            @endif
            <div class="grid gap-6 xl:grid-cols-3">
                <section class="app-card p-6 xl:col-span-2">
                    <h3 class="mb-5 text-xl font-bold text-on-surface">البيانات الشخصية</h3>
                    <dl class="grid gap-4 sm:grid-cols-2">
                        @foreach([
                            'الشركة' => $employee->company?->name_ar,
                            'الفرع' => $employee->branch?->name_ar,
                            'القسم' => $employee->department?->name_ar,
                            'الاسم الكامل بالعربية' => $employee->full_name_arabic,
                            'الاسم الكامل بالإنجليزية' => $employee->full_name_english,
                            'اسم الإقامة بالعربية' => $employee->iqama_full_name_arabic,
                            'اسم الإقامة بالإنجليزية' => $employee->iqama_full_name_english,
                            'اسم الجواز بالعربية' => $employee->passport_full_name_arabic,
                            'اسم الجواز بالإنجليزية' => $employee->passport_full_name_english,
                            'الهوية / الإقامة' => $employee->national_id,
                            'الجنسية' => $employee->nationality,
                            'الجنس' => $employee->gender === 'male' ? 'ذكر' : ($employee->gender === 'female' ? 'أنثى' : null),
                            'تاريخ الميلاد' => $employee->birth_date?->format('Y-m-d'),
                            'الحالة الاجتماعية' => ['single' => 'أعزب', 'married' => 'متزوج', 'divorced' => 'مطلق', 'widowed' => 'أرمل', 'other' => 'أخرى'][$employee->marital_status] ?? null,
                            'العنوان' => $employee->address,
                            'جهة اتصال الطوارئ' => $employee->emergency_contact_name ? $employee->emergency_contact_name . ' - ' . $employee->emergency_contact_phone : $employee->emergency_contact_phone,
                            'انتهاء الإقامة' => $employee->iqama_expiry?->format('Y-m-d'),
                            'انتهاء الجواز' => $employee->passport_expiry?->format('Y-m-d'),
                        ] as $label => $value)
                            <div class="rounded-xl border border-outline-variant/35 bg-surface-container-low p-4">
                                <dt class="text-xs font-bold text-on-surface-variant">{{ $label }}</dt>
                                <dd class="mt-1 font-bold text-on-surface">{{ $value ?? '-' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </section>

                <aside class="space-y-6">
                    <div class="app-card p-6">
                        <h3 class="mb-4 text-xl font-bold text-on-surface">التواصل</h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex items-center justify-between"><span class="text-on-surface-variant">البريد الإلكتروني</span><span dir="ltr" class="font-bold">{{ $employee->email ?? '-' }}</span></div>
                            <div class="flex items-center justify-between"><span class="text-on-surface-variant">الجوال</span><span dir="ltr" class="font-bold">{{ $employee->phone ?? '-' }}</span></div>
                            <div class="flex items-center justify-between"><span class="text-on-surface-variant">جوال إضافي</span><span dir="ltr" class="font-bold">{{ $employee->phone_2 ?? '-' }}</span></div>
                            <div class="flex items-center justify-between border-t border-outline-variant pt-3"><span class="text-on-surface-variant">البنك الأساسي</span><span class="font-bold">{{ $employee->bank_name ?? '-' }}</span></div>
                            <div class="flex items-center justify-between"><span class="text-on-surface-variant">البنك من ملف الموظفين</span><span class="font-bold">{{ $employee->bank ?? '-' }}</span></div>
                        </div>
                    </div>

                    <div class="app-card p-6">
                        <h3 class="mb-4 text-xl font-bold text-on-surface">الراتب</h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between"><span>الأساسي</span><strong>{{ $money($employee->basic_salary) ?? '-' }}</strong></div>
                            <div class="flex justify-between"><span>السكن</span><strong>{{ $money($employee->housing_allowance) ?? '-' }}</strong></div>
                            <div class="flex justify-between"><span>النقل</span><strong>{{ $money($employee->transportation_allowance) ?? '-' }}</strong></div>
                            <div class="flex justify-between"><span>العمل الإضافي</span><strong>{{ $money($employee->overtime) ?? '-' }}</strong></div>
                            <div class="flex justify-between"><span>أجور التدريب والعمل</span><strong>{{ $money($employee->training_labor_wages) ?? '-' }}</strong></div>
                            <div class="flex justify-between"><span>مستحقات سابقة</span><strong>{{ $money($employee->previous_dues) ?? '-' }}</strong></div>
                            <div class="flex justify-between border-t border-outline-variant pt-3 text-base"><span>الإجمالي</span><strong>{{ $money($employee->total ?? $employee->total_salary) ?? '-' }}</strong></div>
                        </div>
                    </div>
                </aside>
            </div>

            <section class="app-card p-6">
                <h3 class="mb-5 text-xl font-bold text-on-surface">بيانات ملف الموظفين</h3>
                <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach([
                        'المسمى النصي' => $employee->job_title,
                        'الفرع النصي' => $employee->branch_text,
                        'تاريخ البداية' => $employee->start_date?->format('Y-m-d'),
                        'تاريخ النهاية' => $employee->end_date?->format('Y-m-d'),
                        'حالة التوظيف' => $employee->employment_status,
                        'الراتب الأساسي بالتأمينات' => $money($employee->basic_salary_gosi),
                        'بدل السكن بالتأمينات' => $money($employee->housing_allowance_gosi),
                        'بنود تأمينات أخرى' => $money($employee->other_gosi_items),
                    ] as $label => $value)
                        <div class="rounded-xl border border-outline-variant/35 bg-surface-container-low p-4">
                            <dt class="text-xs font-bold text-on-surface-variant">{{ $label }}</dt>
                            <dd class="mt-1 font-bold text-on-surface">{{ $value ?? '-' }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            <section class="app-card p-6">
                <h3 class="mb-5 text-xl font-bold text-on-surface">الاستقطاعات والتحويلات</h3>
                <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach([
                        'فرق بدل السكن المسجل' => $money($employee->diff_registered_housing_allowance),
                        'استقطاع الغياب' => $money($employee->absence_deduction),
                        'استقطاع التأخير' => $money($employee->delay_deduction),
                        'استقطاع الإجازات' => $money($employee->leave_deduction),
                        'إنذارات وجزاءات' => $money($employee->warnings_penalties),
                        'استقطاع التأمين' => $money($employee->insurance_deduction),
                        'السلف والقروض' => $money($employee->loans),
                        'التأمينات الاجتماعية للسعودي' => $money($employee->social_insurance_saudi),
                        'إجمالي الاستقطاعات' => $money($employee->total_deductions),
                        'نقداً' => $money($employee->cash),
                        'تحويل الراجحي' => $money($employee->al_rajhi_transfer),
                        'تحويل بنك البلاد' => $money($employee->bank_albilad_transfer),
                        'تحويل بنك الرياض' => $money($employee->riyad_bank_transfer),
                        'الراتب المتبقي' => $money($employee->remaining_salary),
                    ] as $label => $value)
                        <div class="rounded-xl border border-outline-variant/35 bg-surface-container-low p-4">
                            <dt class="text-xs font-bold text-on-surface-variant">{{ $label }}</dt>
                            <dd class="mt-1 font-bold text-on-surface">{{ $value ?? '-' }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        </div>

        <div data-tab-panel="work" class="tab-panel space-y-6" hidden>
            <section class="glass-card rounded-2xl p-6">
                <h3 class="mb-5 text-xl font-bold text-on-surface">التعيين الحالي</h3>
                <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach([
                        'القسم' => $employee->department?->name_ar,
                        'المسمى الوظيفي' => $employee->position?->title_ar,
                        'المسمى النصي من الملف' => $employee->job_title,
                        'الوردية' => $employee->shift?->name_ar,
                        'المدير المباشر' => $employee->manager?->name_ar,
                        'موقع العمل' => $employee->work_location,
                        'الفرع النصي من الملف' => $employee->branch_text,
                        'حالة التوظيف من الملف' => $employee->employment_status,
                        'نوع العقد' => \App\Models\Employee::CONTRACT_TYPE_LABELS_AR[$employee->contract_type] ?? null,
                        'بداية العقد' => $employee->contract_start_date?->format('Y-m-d'),
                        'نهاية العقد' => $employee->contract_end_date?->format('Y-m-d') ?? 'غير محدد',
                        'بداية العمل من الملف' => $employee->start_date?->format('Y-m-d'),
                        'نهاية العمل من الملف' => $employee->end_date?->format('Y-m-d'),
                        'نهاية فترة التجربة' => $employee->probation_end_date?->format('Y-m-d'),
                    ] as $label => $value)
                        <div class="rounded-xl bg-surface-container-low p-4">
                            <dt class="text-xs font-bold text-on-surface-variant">{{ $label }}</dt>
                            <dd class="mt-1 font-bold text-on-surface">{{ $value ?? '-' }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            <section class="glass-card rounded-2xl p-6">
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="text-xl font-bold text-on-surface">سجل العقود</h3>
                    @can('manage-employees')
                        <a href="{{ route('employees.contracts.create', $employee) }}" class="stitch-btn-primary flex items-center gap-2 px-4 py-2 text-sm">
                            <span class="material-symbols-outlined text-base">contract</span>
                            <span>عقد جديد</span>
                        </a>
                    @endcan
                </div>
                @if($employee->contracts->isEmpty())
                    <p class="rounded-xl bg-surface-container-low p-4 text-sm text-on-surface-variant">لا توجد عقود مسجلة لهذا الموظف.</p>
                @else
                    <div class="space-y-4">
                        @foreach($employee->contracts as $contract)
                            <div class="rounded-xl bg-surface-container-low p-4">
                                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                    <div>
                                        <div class="flex items-center gap-3">
                                            <span class="font-bold">عقد {{ \App\Models\Employee::CONTRACT_TYPE_LABELS_AR[$contract->contract_type] ?? $contract->contract_type }}</span>
                                            <span class="rounded-full px-3 py-1 text-xs font-bold
                                                @if($contract->status === 'active') bg-green-100 text-green-800
                                                @elseif($contract->status === 'terminated') bg-red-100 text-red-800
                                                @else bg-surface-container text-on-surface-variant @endif">
                                                @switch($contract->status)
                                                    @case('active') ساري @break
                                                    @case('terminated') منتهي الخدمة @break
                                                    @default منتهي
                                                @endswitch
                                            </span>
                                        </div>
                                        <p class="mt-1 text-sm text-on-surface-variant">
                                            {{ $contract->starts_on->format('Y-m-d') }}
                                            &larr;
                                            {{ $contract->ends_on?->format('Y-m-d') ?? 'غير محدد' }}
                                            @if($contract->contract_number) &middot; رقم العقد: <span dir="ltr">{{ $contract->contract_number }}</span> @endif
                                        </p>
                                        @if($contract->status === 'terminated' && $contract->termination_reason)
                                            <p class="mt-1 text-xs text-red-700">سبب الإنهاء: {{ $contract->termination_reason }}</p>
                                        @endif
                                    </div>
                                    @can('manage-employees')
                                        @if($contract->status === 'active')
                                            <form method="POST" action="{{ route('contracts.terminate', $contract) }}"
                                                  onsubmit="const reason = prompt('سبب إنهاء العقد (إلزامي):'); if (!reason) return false; this.termination_reason.value = reason; return confirm('تأكيد إنهاء العقد؟ هذا الإجراء يُسجل في سجل التدقيق.');">
                                                @csrf
                                                <input type="hidden" name="termination_reason" value="">
                                                <button class="rounded-xl border border-red-300 px-4 py-2 text-sm font-bold text-red-700 hover:bg-red-50">إنهاء العقد</button>
                                            </form>
                                        @endif
                                    @endcan
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="glass-card rounded-2xl p-6">
                <h3 class="mb-5 text-xl font-bold text-on-surface">سجل الحالات</h3>
                @if($employee->statusHistories->isEmpty())
                    <p class="rounded-xl bg-surface-container-low p-4 text-sm text-on-surface-variant">لم تتغير حالة الموظف منذ إنشائه.</p>
                @else
                    <ol class="space-y-3">
                        @foreach($employee->statusHistories as $history)
                            <li class="rounded-xl bg-surface-container-low p-4 text-sm">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div>
                                        <span class="font-bold">{{ \App\Models\Employee::STATUS_LABELS_AR[$history->from_status] ?? $history->from_status }}</span>
                                        <span class="text-on-surface-variant">&larr;</span>
                                        <span class="font-bold text-primary">{{ \App\Models\Employee::STATUS_LABELS_AR[$history->to_status] ?? $history->to_status }}</span>
                                        <span class="text-on-surface-variant">بواسطة {{ $history->changer?->name ?? 'النظام' }}</span>
                                    </div>
                                    <span class="text-xs text-on-surface-variant">{{ $history->created_at->format('Y-m-d H:i') }}</span>
                                </div>
                                @if($history->reason)
                                    <p class="mt-1 text-xs text-on-surface-variant">السبب: {{ $history->reason }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>
        </div>

        <div data-tab-panel="documents" class="tab-panel" hidden>
            <section class="glass-card rounded-2xl p-6">
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="text-xl font-bold text-on-surface">الوثائق</h3>
                    @can('manage-documents')
                        <a href="{{ route('employees.documents.create', $employee) }}" class="stitch-btn-primary flex items-center gap-2 px-4 py-2 text-sm">
                            <span class="material-symbols-outlined text-base">note_add</span>
                            <span>إضافة وثيقة</span>
                        </a>
                    @endcan
                </div>
                @if($employee->documents->isEmpty())
                    <p class="rounded-xl bg-surface-container-low p-4 text-sm text-on-surface-variant">لا توجد وثائق مسجلة لهذا الموظف.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-outline-variant text-start text-xs font-bold text-on-surface-variant">
                                    <th class="p-3">النوع</th>
                                    <th class="p-3">رقم الوثيقة</th>
                                    <th class="p-3">تاريخ الانتهاء</th>
                                    <th class="p-3">الحالة</th>
                                    <th class="p-3">الملف</th>
                                    @can('manage-documents')<th class="p-3"></th>@endcan
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($employee->documents as $document)
                                    <tr class="border-b border-outline-variant/50">
                                        <td class="p-3 font-bold">{{ $document->type?->name_ar }}</td>
                                        <td class="p-3" dir="ltr">{{ $document->document_number ?? '-' }}</td>
                                        <td class="p-3">{{ $document->expiry_date?->format('Y-m-d') ?? '-' }}</td>
                                        <td class="p-3">
                                            @php $status = $document->expiryStatus(); @endphp
                                            <span class="rounded-full px-3 py-1 text-xs font-bold
                                                @if($status === 'expired') bg-red-100 text-red-800
                                                @elseif($status === 'urgent') bg-orange-100 text-orange-800
                                                @elseif($status === 'soon') bg-yellow-100 text-yellow-800
                                                @elseif($status === 'healthy') bg-green-100 text-green-800
                                                @else bg-surface-container text-on-surface-variant @endif">
                                                @switch($status)
                                                    @case('expired') منتهية @break
                                                    @case('urgent') عاجلة @break
                                                    @case('soon') قريبة الانتهاء @break
                                                    @case('healthy') سارية @break
                                                    @default بدون تاريخ
                                                @endswitch
                                            </span>
                                        </td>
                                        <td class="p-3">
                                            @if($document->file_path)
                                                <a href="{{ route('documents.download', $document) }}" class="font-bold text-primary hover:underline">تحميل</a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        @can('manage-documents')
                                            <td class="p-3">
                                                <form method="POST" action="{{ route('documents.destroy', $document) }}" onsubmit="return confirm('هل أنت متأكد من حذف هذه الوثيقة؟ سيبقى أثرها في سجل التدقيق.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="text-red-600 hover:underline">حذف</button>
                                                </form>
                                            </td>
                                        @endcan
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>

        <div data-tab-panel="leave" class="tab-panel space-y-6" hidden>
            <section class="glass-card rounded-2xl p-6">
                <h3 class="mb-4 text-xl font-bold text-on-surface">أرصدة الإجازات</h3>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse($employee->leaveBalances as $balance)
                        <div class="rounded-xl bg-surface-container-low p-4">
                            <div class="flex items-center justify-between">
                                <div class="font-bold">{{ $balance->leaveType?->name_ar }}</div>
                                <span class="rounded-full bg-primary px-2 py-1 text-xs font-bold text-on-primary">
                                    {{ number_format($balance->entitled_days + $balance->carried_days - $balance->used_days, 2) }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-on-surface-variant">لا توجد أرصدة إجازات مسجلة.</p>
                    @endforelse
                </div>
            </section>

            <section class="glass-card rounded-2xl p-6">
                <h3 class="mb-4 text-xl font-bold text-on-surface">أحدث طلبات الإجازة</h3>
                @if($employee->leaveRequests->isEmpty())
                    <p class="rounded-xl bg-surface-container-low p-4 text-sm text-on-surface-variant">لا توجد طلبات إجازة سابقة.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-outline-variant text-start text-xs font-bold text-on-surface-variant">
                                    <th class="p-3">نوع الإجازة</th>
                                    <th class="p-3">من</th>
                                    <th class="p-3">إلى</th>
                                    <th class="p-3">عدد الأيام</th>
                                    <th class="p-3">الحالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($employee->leaveRequests as $request)
                                    <tr class="border-b border-outline-variant/50">
                                        <td class="p-3 font-bold">{{ $request->leaveType?->name_ar }}</td>
                                        <td class="p-3">{{ $request->starts_on->format('Y-m-d') }}</td>
                                        <td class="p-3">{{ $request->ends_on->format('Y-m-d') }}</td>
                                        <td class="p-3">{{ number_format($request->days, 2) }}</td>
                                        <td class="p-3">
                                            <span class="rounded-full px-3 py-1 text-xs font-bold
                                                @if($request->status === 'approved') bg-green-100 text-green-800
                                                @elseif($request->status === 'rejected') bg-red-100 text-red-800
                                                @else bg-yellow-100 text-yellow-800 @endif">
                                                @switch($request->status)
                                                    @case('approved') موافق عليها @break
                                                    @case('rejected') مرفوضة @break
                                                    @default قيد الانتظار
                                                @endswitch
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>

        <div data-tab-panel="attendance" class="tab-panel" hidden>
            <section class="glass-card rounded-2xl p-6">
                <h3 class="mb-4 text-xl font-bold text-on-surface">أحدث سجلات الحضور</h3>
                @if($employee->attendanceRecords->isEmpty())
                    <p class="rounded-xl bg-surface-container-low p-4 text-sm text-on-surface-variant">لا توجد سجلات حضور مسجلة.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-outline-variant text-start text-xs font-bold text-on-surface-variant">
                                    <th class="p-3">التاريخ</th>
                                    <th class="p-3">الحضور</th>
                                    <th class="p-3">الانصراف</th>
                                    <th class="p-3">الحالة</th>
                                    <th class="p-3">دقائق التأخير</th>
                                    <th class="p-3">دقائق الغياب</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($employee->attendanceRecords as $record)
                                    <tr class="border-b border-outline-variant/50">
                                        <td class="p-3 font-bold">{{ $record->work_date->format('Y-m-d') }}</td>
                                        <td class="p-3" dir="ltr">{{ $record->check_in ?? '-' }}</td>
                                        <td class="p-3" dir="ltr">{{ $record->check_out ?? '-' }}</td>
                                        <td class="p-3">
                                            <span class="rounded-full px-3 py-1 text-xs font-bold
                                                @if($record->status === 'present') bg-green-100 text-green-800
                                                @elseif($record->status === 'absent') bg-red-100 text-red-800
                                                @else bg-yellow-100 text-yellow-800 @endif">
                                                @switch($record->status)
                                                    @case('present') حاضر @break
                                                    @case('absent') غائب @break
                                                    @default {{ $record->status }}
                                                @endswitch
                                            </span>
                                        </td>
                                        <td class="p-3">{{ $record->late_minutes }}</td>
                                        <td class="p-3">{{ $record->absence_minutes }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>

        @if($canSeePayroll)
            <div data-tab-panel="payroll" class="tab-panel" hidden>
                <section class="glass-card rounded-2xl p-6">
                    <h3 class="mb-4 text-xl font-bold text-on-surface">كشف الرواتب</h3>
                    @if($employee->payrollItems->isEmpty())
                        <p class="rounded-xl bg-surface-container-low p-4 text-sm text-on-surface-variant">لا توجد مسيرات رواتب مسجلة لهذا الموظف.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-outline-variant text-start text-xs font-bold text-on-surface-variant">
                                        <th class="p-3">الفترة</th>
                                        <th class="p-3">الإجمالي</th>
                                        <th class="p-3">الاستقطاعات</th>
                                        <th class="p-3">الصافي</th>
                                        <th class="p-3">حالة المسير</th>
                                        <th class="p-3"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($employee->payrollItems as $item)
                                        <tr class="border-b border-outline-variant/50">
                                            <td class="p-3 font-bold">{{ $item->cycle?->year }} / {{ str_pad((string) $item->cycle?->month, 2, '0', STR_PAD_LEFT) }}</td>
                                            <td class="p-3">{{ number_format($item->gross_total, 2) }}</td>
                                            <td class="p-3">{{ number_format($item->total_deductions, 2) }}</td>
                                            <td class="p-3 font-black text-primary">{{ number_format($item->net_salary, 2) }}</td>
                                            <td class="p-3">
                                                @php
                                                    $cycleStatusMeta = [
                                                        'draft' => ['مسودة', 'bg-surface-container text-on-surface-variant'],
                                                        'under_review' => ['قيد المراجعة', 'bg-yellow-100 text-yellow-800'],
                                                        'approved' => ['معتمد', 'bg-blue-100 text-blue-800'],
                                                        'locked' => ['مقفل', 'bg-green-100 text-green-800'],
                                                    ][$item->cycle?->status] ?? [$item->cycle?->status, 'bg-surface-container text-on-surface-variant'];
                                                @endphp
                                                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $cycleStatusMeta[1] }}">{{ $cycleStatusMeta[0] }}</span>
                                            </td>
                                            <td class="p-3">
                                                <div class="flex items-center gap-3">
                                                    {{-- Self-service payslips only for locked (official) runs. --}}
                                                    @if(auth()->user()->can('view-payroll') || $item->cycle?->status === 'locked')
                                                        <a href="{{ route('payroll.payslip', [$item->payroll_cycle_id, $item]) }}" class="font-bold text-primary hover:underline">قسيمة الراتب</a>
                                                    @endif
                                                    @can('view-payroll')
                                                        <a href="{{ route('payroll.show', $item->payroll_cycle_id) }}" class="font-bold text-primary hover:underline">عرض المسير</a>
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>
            </div>
        @endif

        @if($canSeeActivity)
            <div data-tab-panel="activity" class="tab-panel" hidden>
                <section class="glass-card rounded-2xl p-6">
                    <h3 class="mb-4 text-xl font-bold text-on-surface">سجل النشاط</h3>
                    @if($timeline->isEmpty())
                        <p class="rounded-xl bg-surface-container-low p-4 text-sm text-on-surface-variant">لا توجد تغييرات مسجلة بعد.</p>
                    @else
                        <ol class="space-y-3">
                            @foreach($timeline as $activity)
                                <li class="rounded-xl bg-surface-container-low p-4">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <div class="text-sm">
                                            <span class="font-bold">{{ $activity->causer?->name ?? 'النظام' }}</span>
                                            <span class="text-on-surface-variant">
                                                @switch($activity->event)
                                                    @case('created') أنشأ سجلاً @break
                                                    @case('updated') عدّل بيانات @break
                                                    @case('deleted') حذف سجلاً @break
                                                    @default {{ $activity->event }}
                                                @endswitch
                                                — {{ $activity->log_name }}
                                            </span>
                                        </div>
                                        <span class="text-xs text-on-surface-variant">{{ $activity->created_at->diffForHumans() }}</span>
                                    </div>
                                    @if($activity->event === 'updated' && $activity->properties->get('attributes'))
                                        <ul class="mt-2 space-y-1 text-xs text-on-surface-variant">
                                            @foreach($activity->properties->get('attributes') as $field => $newValue)
                                                <li>
                                                    <span class="font-bold">{{ $field }}</span>:
                                                    {{ $activity->properties->get('old')[$field] ?? '-' }}
                                                    &larr;
                                                    {{ $newValue }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </section>
            </div>
        @endif
    </div>

    <script>
        (function () {
            const container = document.querySelector('[data-tabs]');
            if (!container) return;

            const triggers = Array.from(document.querySelectorAll('[data-tab-trigger]'));
            const panels = Array.from(document.querySelectorAll('[data-tab-panel]'));

            function activate(key) {
                if (!triggers.some((t) => t.dataset.tabTrigger === key)) {
                    key = triggers[0]?.dataset.tabTrigger;
                }

                triggers.forEach((trigger) => {
                    const active = trigger.dataset.tabTrigger === key;
                    trigger.classList.toggle('bg-primary', active);
                    trigger.classList.toggle('text-on-primary', active);
                    trigger.classList.toggle('text-on-surface-variant', !active);
                });

                panels.forEach((panel) => {
                    panel.hidden = panel.dataset.tabPanel !== key;
                });

                history.replaceState(null, '', '#' + key);
            }

            triggers.forEach((trigger) => {
                trigger.addEventListener('click', () => activate(trigger.dataset.tabTrigger));
            });

            activate(window.location.hash ? window.location.hash.slice(1) : triggers[0]?.dataset.tabTrigger);
        })();
    </script>
@endsection
