@if ($errors->any())
    <div class="rounded-2xl border border-red-300 bg-red-50 p-4 text-sm text-red-800">
        <p class="mb-2 font-bold">يرجى تصحيح الأخطاء التالية:</p>
        <ul class="list-inside list-disc space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<section class="space-y-4">
    <h3 class="border-b border-outline-variant pb-2 text-lg font-bold text-primary">البيانات الأساسية</h3>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">الاسم بالعربية *</label>
            <input name="name_ar" value="{{ old('name_ar', $employee->name_ar) }}" class="stitch-input w-full p-3" required>
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">الاسم بالإنجليزية</label>
            <input name="name_en" value="{{ old('name_en', $employee->name_en) }}" class="stitch-input w-full p-3" dir="ltr">
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">الرقم الوظيفي</label>
            <input name="employee_code" value="{{ old('employee_code', $employee->employee_code) }}" class="stitch-input w-full p-3" dir="ltr">
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">الرقم المالي</label>
            <input name="financial_employee_id" value="{{ old('financial_employee_id', $employee->financial_employee_id) }}" class="stitch-input w-full p-3" dir="ltr">
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">رقم الموظف في الموارد البشرية</label>
            <input name="hr_employee_id" value="{{ old('hr_employee_id', $employee->hr_employee_id) }}" class="stitch-input w-full p-3" dir="ltr">
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">رقم الهوية / الإقامة</label>
            <input name="national_id" value="{{ old('national_id', $employee->national_id) }}" class="stitch-input w-full p-3" dir="ltr" maxlength="10" inputmode="numeric">
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">التصنيف *</label>
            <select name="saudi_non_saudi" class="stitch-input w-full p-3" required>
                <option value="saudi" @selected(old('saudi_non_saudi', $employee->saudi_non_saudi) === 'saudi')>سعودي</option>
                <option value="non_saudi" @selected(old('saudi_non_saudi', $employee->saudi_non_saudi) === 'non_saudi')>غير سعودي</option>
            </select>
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">الجنسية</label>
            <input name="nationality" value="{{ old('nationality', $employee->nationality) }}" class="stitch-input w-full p-3">
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">الجنس</label>
            <select name="gender" class="stitch-input w-full p-3">
                <option value="">—</option>
                <option value="male" @selected(old('gender', $employee->gender) === 'male')>ذكر</option>
                <option value="female" @selected(old('gender', $employee->gender) === 'female')>أنثى</option>
            </select>
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">تاريخ الميلاد</label>
            <input name="birth_date" type="date" value="{{ old('birth_date', $employee->birth_date?->toDateString()) }}" class="stitch-input w-full p-3">
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">البريد الإلكتروني</label>
            <input name="email" type="email" value="{{ old('email', $employee->email) }}" class="stitch-input w-full p-3" dir="ltr">
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">رقم الجوال</label>
            <input name="phone" value="{{ old('phone', $employee->phone) }}" class="stitch-input w-full p-3" dir="ltr" maxlength="10" inputmode="numeric">
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">رقم الجوال 2</label>
            <input name="phone_2" value="{{ old('phone_2', $employee->phone_2) }}" class="stitch-input w-full p-3" dir="ltr" maxlength="10" inputmode="numeric">
        </div>
    </div>
</section>

<section class="space-y-4">
    <h3 class="border-b border-outline-variant pb-2 text-lg font-bold text-primary">الأسماء الرسمية حسب الوثائق</h3>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">الاسم الكامل بالعربية</label>
            <input name="full_name_arabic" value="{{ old('full_name_arabic', $employee->full_name_arabic) }}" class="stitch-input w-full p-3">
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">Full Name English</label>
            <input name="full_name_english" value="{{ old('full_name_english', $employee->full_name_english) }}" class="stitch-input w-full p-3" dir="ltr">
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">الاسم في الإقامة - عربي</label>
            <input name="iqama_full_name_arabic" value="{{ old('iqama_full_name_arabic', $employee->iqama_full_name_arabic) }}" class="stitch-input w-full p-3">
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">Iqama Name - English</label>
            <input name="iqama_full_name_english" value="{{ old('iqama_full_name_english', $employee->iqama_full_name_english) }}" class="stitch-input w-full p-3" dir="ltr">
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">الاسم في الجواز - عربي</label>
            <input name="passport_full_name_arabic" value="{{ old('passport_full_name_arabic', $employee->passport_full_name_arabic) }}" class="stitch-input w-full p-3">
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">Passport Name - English</label>
            <input name="passport_full_name_english" value="{{ old('passport_full_name_english', $employee->passport_full_name_english) }}" class="stitch-input w-full p-3" dir="ltr">
        </div>
    </div>
</section>

<section class="space-y-4">
    <h3 class="border-b border-outline-variant pb-2 text-lg font-bold text-primary">التعيين الإداري</h3>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">الشركة *</label>
            <select name="company_id" id="company-select" class="stitch-input w-full p-3" required>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}" @selected((int) old('company_id', $employee->company_id) === $company->id)>{{ $company->name_ar }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">الفرع</label>
            <select name="branch_id" id="branch-select" class="stitch-input w-full p-3">
                <option value="">—</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" data-company="{{ $branch->company_id }}" @selected((int) old('branch_id', $employee->branch_id) === $branch->id)>{{ $branch->name_ar }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">القسم</label>
            <select name="department_id" id="department-select" class="stitch-input w-full p-3">
                <option value="">—</option>
                @foreach($departments as $department)
                    <option value="{{ $department->id }}" data-branch="{{ $department->branch_id }}" @selected((int) old('department_id', $employee->department_id) === $department->id)>{{ $department->name_ar }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">المسمى الوظيفي</label>
            <select name="position_id" class="stitch-input w-full p-3">
                <option value="">—</option>
                @foreach($positions as $position)
                    <option value="{{ $position->id }}" @selected((int) old('position_id', $employee->position_id) === $position->id)>{{ $position->title_ar }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">الوردية</label>
            <select name="shift_id" class="stitch-input w-full p-3">
                <option value="">—</option>
                @foreach($shifts as $shift)
                    <option value="{{ $shift->id }}" @selected((int) old('shift_id', $employee->shift_id) === $shift->id)>{{ $shift->name_ar }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">الحالة *</label>
            <select name="status" class="stitch-input w-full p-3" required>
                @foreach(\App\Models\Employee::STATUSES as $statusOption)
                    <option value="{{ $statusOption }}" @selected(old('status', $employee->status) === $statusOption)>{{ \App\Models\Employee::STATUS_LABELS_AR[$statusOption] }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">المدير المباشر</label>
            <select name="manager_id" id="manager-select" class="stitch-input w-full p-3">
                <option value="">—</option>
                @foreach($managers as $manager)
                    @continue($employee->exists && $manager->id === $employee->id)
                    <option value="{{ $manager->id }}" data-company="{{ $manager->company_id }}" @selected((int) old('manager_id', $employee->manager_id) === $manager->id)>{{ $manager->name_ar }} - {{ $manager->employee_code }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">موقع العمل</label>
            <input name="work_location" value="{{ old('work_location', $employee->work_location) }}" class="stitch-input w-full p-3">
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">المسمى الوظيفي النصي</label>
            <input name="job_title" value="{{ old('job_title', $employee->job_title) }}" class="stitch-input w-full p-3">
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">الفرع النصي من ملف الرواتب</label>
            <input name="branch_text" value="{{ old('branch_text', $employee->branch_text) }}" class="stitch-input w-full p-3">
        </div>
    </div>
</section>

<section class="space-y-4">
    <h3 class="border-b border-outline-variant pb-2 text-lg font-bold text-primary">بيانات إضافية</h3>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">الحالة الاجتماعية</label>
            <select name="marital_status" class="stitch-input w-full p-3">
                <option value="">—</option>
                <option value="single" @selected(old('marital_status', $employee->marital_status) === 'single')>أعزب</option>
                <option value="married" @selected(old('marital_status', $employee->marital_status) === 'married')>متزوج</option>
                <option value="divorced" @selected(old('marital_status', $employee->marital_status) === 'divorced')>مطلق</option>
                <option value="widowed" @selected(old('marital_status', $employee->marital_status) === 'widowed')>أرمل</option>
                <option value="other" @selected(old('marital_status', $employee->marital_status) === 'other')>أخرى</option>
            </select>
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">العنوان</label>
            <input name="address" value="{{ old('address', $employee->address) }}" class="stitch-input w-full p-3">
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">اسم جهة اتصال الطوارئ</label>
            <input name="emergency_contact_name" value="{{ old('emergency_contact_name', $employee->emergency_contact_name) }}" class="stitch-input w-full p-3">
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">جوال جهة اتصال الطوارئ</label>
            <input name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $employee->emergency_contact_phone) }}" class="stitch-input w-full p-3" dir="ltr" maxlength="20" inputmode="numeric">
        </div>
    </div>
</section>

<section class="space-y-4">
    <h3 class="border-b border-outline-variant pb-2 text-lg font-bold text-primary">الإقامة والجواز والعقد</h3>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">تاريخ انتهاء الإقامة</label>
            <input name="iqama_expiry" type="date" value="{{ old('iqama_expiry', $employee->iqama_expiry?->toDateString()) }}" class="stitch-input w-full p-3">
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">رقم الجواز</label>
            <input name="passport_id" value="{{ old('passport_id', $employee->passport_id) }}" class="stitch-input w-full p-3" dir="ltr">
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">تاريخ انتهاء الجواز</label>
            <input name="passport_expiry" type="date" value="{{ old('passport_expiry', $employee->passport_expiry?->toDateString()) }}" class="stitch-input w-full p-3">
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">نوع العقد</label>
            <select name="contract_type" class="stitch-input w-full p-3">
                <option value="">—</option>
                @foreach(\App\Models\Employee::CONTRACT_TYPES as $contractTypeOption)
                    <option value="{{ $contractTypeOption }}" @selected(old('contract_type', $employee->contract_type) === $contractTypeOption)>{{ \App\Models\Employee::CONTRACT_TYPE_LABELS_AR[$contractTypeOption] }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">بداية العقد</label>
            <input name="contract_start_date" type="date" value="{{ old('contract_start_date', $employee->contract_start_date?->toDateString()) }}" class="stitch-input w-full p-3">
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">نهاية العقد</label>
            <input name="contract_end_date" type="date" value="{{ old('contract_end_date', $employee->contract_end_date?->toDateString()) }}" class="stitch-input w-full p-3">
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">تاريخ البداية حسب ملف الرواتب</label>
            <input name="start_date" type="date" value="{{ old('start_date', $employee->start_date?->toDateString()) }}" class="stitch-input w-full p-3">
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">تاريخ النهاية حسب ملف الرواتب</label>
            <input name="end_date" type="date" value="{{ old('end_date', $employee->end_date?->toDateString()) }}" class="stitch-input w-full p-3">
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">نهاية فترة التجربة</label>
            <input name="probation_end_date" type="date" value="{{ old('probation_end_date', $employee->probation_end_date?->toDateString()) }}" class="stitch-input w-full p-3">
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">حالة التوظيف من ملف الرواتب</label>
            <input name="employment_status" value="{{ old('employment_status', $employee->employment_status) }}" class="stitch-input w-full p-3">
        </div>
    </div>
</section>

<section class="space-y-4">
    <h3 class="border-b border-outline-variant pb-2 text-lg font-bold text-primary">الراتب والبنك</h3>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">الراتب الأساسي *</label>
            <input name="basic_salary" type="number" step="0.01" min="0" value="{{ old('basic_salary', $employee->basic_salary) }}" class="stitch-input w-full p-3" dir="ltr" required>
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">العمل الإضافي</label>
            <input name="overtime" type="number" step="0.01" min="0" value="{{ old('overtime', $employee->overtime) }}" class="stitch-input w-full p-3" dir="ltr">
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">بدل السكن</label>
            <input name="housing_allowance" type="number" step="0.01" min="0" value="{{ old('housing_allowance', $employee->housing_allowance) }}" class="stitch-input w-full p-3" dir="ltr">
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">بدل المواصلات</label>
            <input name="transportation_allowance" type="number" step="0.01" min="0" value="{{ old('transportation_allowance', $employee->transportation_allowance) }}" class="stitch-input w-full p-3" dir="ltr">
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">بدلات أخرى</label>
            <input name="other_allowances" type="number" step="0.01" min="0" value="{{ old('other_allowances', $employee->other_allowances) }}" class="stitch-input w-full p-3" dir="ltr">
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">أجور العمالة تحت التدريب</label>
            <input name="training_labor_wages" type="number" step="0.01" min="0" value="{{ old('training_labor_wages', $employee->training_labor_wages) }}" class="stitch-input w-full p-3" dir="ltr">
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">مستحقات سابقة</label>
            <input name="previous_dues" type="number" step="0.01" min="0" value="{{ old('previous_dues', $employee->previous_dues) }}" class="stitch-input w-full p-3" dir="ltr">
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">الإجمالي</label>
            <input name="total" type="number" step="0.01" min="0" value="{{ old('total', $employee->total) }}" class="stitch-input w-full p-3" dir="ltr">
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">الأجر الأساسي الخاضع للتأمينات</label>
            <input name="gosi_basic_salary" type="number" step="0.01" min="0" value="{{ old('gosi_basic_salary', $employee->gosi_basic_salary) }}" class="stitch-input w-full p-3" dir="ltr">
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">بدل السكن الخاضع للتأمينات</label>
            <input name="gosi_housing_allowance" type="number" step="0.01" min="0" value="{{ old('gosi_housing_allowance', $employee->gosi_housing_allowance) }}" class="stitch-input w-full p-3" dir="ltr">
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">GOSI Basic Salary</label>
            <input name="basic_salary_gosi" type="number" step="0.01" min="0" value="{{ old('basic_salary_gosi', $employee->basic_salary_gosi) }}" class="stitch-input w-full p-3" dir="ltr">
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">GOSI Housing Allowance</label>
            <input name="housing_allowance_gosi" type="number" step="0.01" min="0" value="{{ old('housing_allowance_gosi', $employee->housing_allowance_gosi) }}" class="stitch-input w-full p-3" dir="ltr">
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">بنود تأمينات أخرى</label>
            <input name="other_gosi_items" type="number" step="0.01" min="0" value="{{ old('other_gosi_items', $employee->other_gosi_items) }}" class="stitch-input w-full p-3" dir="ltr">
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">فرق بدل السكن المسجل</label>
            <input name="diff_registered_housing_allowance" type="number" step="0.01" value="{{ old('diff_registered_housing_allowance', $employee->diff_registered_housing_allowance) }}" class="stitch-input w-full p-3" dir="ltr">
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">البنك</label>
            <input name="bank_name" value="{{ old('bank_name', $employee->bank_name) }}" class="stitch-input w-full p-3">
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">البنك حسب ملف الرواتب</label>
            <input name="bank" value="{{ old('bank', $employee->bank) }}" class="stitch-input w-full p-3">
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-on-surface-variant">الآيبان (IBAN)</label>
            <input name="iban" value="{{ old('iban', $employee->iban) }}" class="stitch-input w-full p-3" dir="ltr" maxlength="24" placeholder="SA0000000000000000000000">
        </div>
    </div>
</section>

<section class="space-y-4">
    <h3 class="border-b border-outline-variant pb-2 text-lg font-bold text-primary">الاستقطاعات والتحويلات</h3>
    <div class="grid gap-4 sm:grid-cols-2">
        @foreach([
            'absence_deduction' => 'خصم الغياب',
            'delay_deduction' => 'خصم التأخير',
            'leave_deduction' => 'خصم الإجازات',
            'warnings_penalties' => 'الإنذارات والجزاءات',
            'insurance_deduction' => 'خصم التأمين',
            'loans' => 'السلف والقروض',
            'social_insurance_saudi' => 'التأمينات الاجتماعية للسعوديين',
            'total_deductions' => 'إجمالي الاستقطاعات',
            'cash' => 'نقدي',
            'al_rajhi_transfer' => 'تحويل الراجحي',
            'bank_albilad_transfer' => 'تحويل بنك البلاد',
            'riyad_bank_transfer' => 'تحويل بنك الرياض',
            'remaining_salary' => 'المتبقي من الراتب',
        ] as $field => $label)
            <div>
                <label class="mb-2 block text-sm font-bold text-on-surface-variant">{{ $label }}</label>
                <input name="{{ $field }}" type="number" step="0.01" @if($field !== 'remaining_salary') min="0" @endif value="{{ old($field, $employee->{$field}) }}" class="stitch-input w-full p-3" dir="ltr">
            </div>
        @endforeach
    </div>
</section>

<script>
    (function () {
        const companySelect = document.getElementById('company-select');
        const branchSelect = document.getElementById('branch-select');
        const departmentSelect = document.getElementById('department-select');
        const managerSelect = document.getElementById('manager-select');

        function filterOptions(select, attribute, parentValue) {
            let selectedStillVisible = false;
            for (const option of select.options) {
                if (!option.value) continue;
                const visible = option.dataset[attribute] === parentValue;
                option.hidden = !visible;
                if (visible && option.selected) selectedStillVisible = true;
            }
            if (!selectedStillVisible) select.value = '';
        }

        function syncBranches() {
            filterOptions(branchSelect, 'company', companySelect.value);
            filterOptions(managerSelect, 'company', companySelect.value);
            syncDepartments();
        }

        function syncDepartments() {
            filterOptions(departmentSelect, 'branch', branchSelect.value);
        }

        companySelect.addEventListener('change', syncBranches);
        branchSelect.addEventListener('change', syncDepartments);
        syncBranches();
    })();
</script>
