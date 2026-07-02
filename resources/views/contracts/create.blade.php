@extends('layouts.app')

@section('title', 'عقد جديد')

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="overflow-hidden rounded-3xl bg-surface shadow-2xl ring-1 ring-outline-variant">
            <div class="flex items-center justify-between bg-primary p-6 text-on-primary">
                <div>
                    <h2 class="text-2xl font-bold">عقد عمل جديد</h2>
                    <p class="mt-1 text-sm opacity-80">{{ $employee->name_ar }} — {{ $employee->company?->name_ar }}</p>
                </div>
                <a href="{{ route('employees.show', $employee) }}" class="rounded-full p-2 hover:bg-white/20">إغلاق</a>
            </div>
            <form method="POST" action="{{ route('employees.contracts.store', $employee) }}" class="space-y-6 p-8">
                @csrf
                @if ($errors->any())
                    <div class="rounded-2xl border border-red-300 bg-red-50 p-4 text-sm text-red-800">
                        <ul class="list-inside list-disc space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-bold text-on-surface-variant">رقم العقد</label>
                        <input name="contract_number" value="{{ old('contract_number') }}" class="stitch-input w-full p-3" dir="ltr">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-bold text-on-surface-variant">نوع العقد *</label>
                        <select name="contract_type" class="stitch-input w-full p-3" required>
                            @foreach(\App\Models\Employee::CONTRACT_TYPES as $contractTypeOption)
                                <option value="{{ $contractTypeOption }}" @selected(old('contract_type', $employee->contract_type) === $contractTypeOption)>{{ \App\Models\Employee::CONTRACT_TYPE_LABELS_AR[$contractTypeOption] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-bold text-on-surface-variant">تاريخ البداية *</label>
                        <input name="starts_on" type="date" value="{{ old('starts_on', now()->toDateString()) }}" class="stitch-input w-full p-3" required>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-bold text-on-surface-variant">تاريخ النهاية (للعقد محدد المدة)</label>
                        <input name="ends_on" type="date" value="{{ old('ends_on') }}" class="stitch-input w-full p-3">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-bold text-on-surface-variant">نهاية فترة التجربة</label>
                        <input name="probation_ends_on" type="date" value="{{ old('probation_ends_on') }}" class="stitch-input w-full p-3">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-bold text-on-surface-variant">الراتب الأساسي *</label>
                        <input name="basic_salary" type="number" step="0.01" min="0" value="{{ old('basic_salary', $employee->basic_salary) }}" class="stitch-input w-full p-3" dir="ltr" required>
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
                </div>
                <div class="flex gap-4 pt-4">
                    <button class="stitch-btn-primary flex-1 py-4">إنشاء العقد</button>
                    <a href="{{ route('employees.show', $employee) }}" class="rounded-xl border border-outline px-8 py-4 font-bold hover:bg-surface-container">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
