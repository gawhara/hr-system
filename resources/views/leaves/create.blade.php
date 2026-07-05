@extends('layouts.app')

@section('title', 'طلب إجازة جديد')

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <section class="overflow-hidden rounded-3xl border border-outline-variant/50 bg-white shadow-[0_16px_38px_rgba(25,28,30,0.05)]">
            <div class="flex items-center justify-between bg-gradient-to-br from-[#170040] via-[#2e1065] to-[#6b38d4] p-6 text-white">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-white/60">Leave Request</p>
                    <h2 class="mt-1 text-2xl font-black">طلب إجازة جديد</h2>
                </div>
                <a href="{{ route('leaves.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/10 px-4 py-2 text-sm font-bold text-white hover:bg-white/18">
                    <span class="material-symbols-outlined text-lg">close</span>
                    <span>إغلاق</span>
                </a>
            </div>
            <form method="POST" action="{{ route('leaves.store') }}" class="space-y-5 p-6">
                @csrf
                <div>
                    <label class="mb-2 block text-xs font-bold text-on-surface-variant">الموظف</label>
                    <select name="employee_id" class="stitch-input w-full px-3 py-3" required>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name_ar }} - {{ $employee->employee_code }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-bold text-on-surface-variant">نوع الإجازة</label>
                    <select name="leave_type_id" class="stitch-input w-full px-3 py-3" required>
                        @foreach($leaveTypes as $leaveType)
                            <option value="{{ $leaveType->id }}">{{ $leaveType->name_ar }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-xs font-bold text-on-surface-variant">من تاريخ</label>
                        <input name="starts_on" type="date" value="{{ old('starts_on', now()->addDay()->toDateString()) }}" class="stitch-input w-full px-3 py-3" required>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-bold text-on-surface-variant">إلى تاريخ</label>
                        <input name="ends_on" type="date" value="{{ old('ends_on', now()->addDays(2)->toDateString()) }}" class="stitch-input w-full px-3 py-3" required>
                    </div>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-bold text-on-surface-variant">ملاحظات إضافية</label>
                    <textarea name="reason" class="stitch-input h-28 w-full px-3 py-3" placeholder="اكتب سبباً أو تفاصيل إضافية...">{{ old('reason') }}</textarea>
                </div>
                <div class="flex flex-col gap-3 pt-2 sm:flex-row">
                    <button class="stitch-btn-primary flex-1 py-4">إرسال الطلب</button>
                    <a href="{{ route('leaves.index') }}" class="inline-flex items-center justify-center rounded-xl border border-outline-variant px-8 py-4 font-bold text-on-surface-variant hover:bg-surface-container">إلغاء</a>
                </div>
            </form>
        </section>
    </div>
@endsection
