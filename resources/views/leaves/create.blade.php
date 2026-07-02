@extends('layouts.app')

@section('title', 'طلب إجازة جديد')

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="overflow-hidden rounded-3xl bg-surface shadow-2xl ring-1 ring-outline-variant">
            <div class="flex items-center justify-between bg-primary p-6 text-on-primary">
                <h2 class="text-2xl font-bold">طلب إجازة جديد</h2>
                <a href="{{ route('leaves.index') }}" class="rounded-full p-2 hover:bg-white/20">إغلاق</a>
            </div>
            <form method="POST" action="{{ route('leaves.store') }}" class="space-y-6 p-8">
                @csrf
                <div>
                    <label class="mb-2 block text-sm font-bold text-on-surface-variant">الموظف</label>
                    <select name="employee_id" class="stitch-input w-full p-3" required>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name_ar }} - {{ $employee->employee_code }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-bold text-on-surface-variant">نوع الإجازة</label>
                    <select name="leave_type_id" class="stitch-input w-full p-3" required>
                        @foreach($leaveTypes as $leaveType)
                            <option value="{{ $leaveType->id }}">{{ $leaveType->name_ar }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-bold text-on-surface-variant">من تاريخ</label>
                        <input name="starts_on" type="date" value="{{ old('starts_on', now()->addDay()->toDateString()) }}" class="stitch-input w-full p-3" required>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-bold text-on-surface-variant">إلى تاريخ</label>
                        <input name="ends_on" type="date" value="{{ old('ends_on', now()->addDays(2)->toDateString()) }}" class="stitch-input w-full p-3" required>
                    </div>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-bold text-on-surface-variant">ملاحظات إضافية</label>
                    <textarea name="reason" class="stitch-input h-24 w-full p-3" placeholder="اكتب سبباً أو تفاصيل إضافية...">{{ old('reason') }}</textarea>
                </div>
                <div class="flex gap-4 pt-4">
                    <button class="stitch-btn-primary flex-1 py-4">إرسال الطلب</button>
                    <a href="{{ route('leaves.index') }}" class="rounded-xl border border-outline px-8 py-4 font-bold hover:bg-surface-container">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
