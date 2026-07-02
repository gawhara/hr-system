@extends('layouts.app')

@section('title', 'إضافة وثيقة')

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="overflow-hidden rounded-3xl bg-surface shadow-2xl ring-1 ring-outline-variant">
            <div class="flex items-center justify-between bg-primary p-6 text-on-primary">
                <div>
                    <h2 class="text-2xl font-bold">إضافة وثيقة</h2>
                    <p class="mt-1 text-sm opacity-80">{{ $employee->name_ar }} — {{ $employee->employee_code }}</p>
                </div>
                <a href="{{ route('employees.show', $employee) }}" class="rounded-full p-2 hover:bg-white/20">إغلاق</a>
            </div>
            <form method="POST" action="{{ route('employees.documents.store', $employee) }}" enctype="multipart/form-data" class="space-y-6 p-8">
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
                <div>
                    <label class="mb-2 block text-sm font-bold text-on-surface-variant">نوع الوثيقة *</label>
                    <select name="document_type_id" class="stitch-input w-full p-3" required>
                        @foreach($documentTypes as $type)
                            <option value="{{ $type->id }}" @selected((int) old('document_type_id') === $type->id)>{{ $type->name_ar }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-bold text-on-surface-variant">رقم الوثيقة</label>
                    <input name="document_number" value="{{ old('document_number') }}" class="stitch-input w-full p-3" dir="ltr">
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-bold text-on-surface-variant">تاريخ الإصدار</label>
                        <input name="issue_date" type="date" value="{{ old('issue_date') }}" class="stitch-input w-full p-3">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-bold text-on-surface-variant">تاريخ الانتهاء</label>
                        <input name="expiry_date" type="date" value="{{ old('expiry_date') }}" class="stitch-input w-full p-3">
                    </div>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-bold text-on-surface-variant">الملف (PDF / صورة، حتى 5MB)</label>
                    <input name="file" type="file" accept=".pdf,.jpg,.jpeg,.png" class="stitch-input w-full p-3">
                    <p class="mt-1 text-xs text-on-surface-variant">يُحفظ الملف محلياً في الفرع ويُرفع للخادم المركزي عند توفر الاتصال.</p>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-bold text-on-surface-variant">ملاحظات</label>
                    <textarea name="notes" class="stitch-input h-24 w-full p-3">{{ old('notes') }}</textarea>
                </div>
                <div class="flex gap-4 pt-4">
                    <button class="stitch-btn-primary flex-1 py-4">حفظ الوثيقة</button>
                    <a href="{{ route('employees.show', $employee) }}" class="rounded-xl border border-outline px-8 py-4 font-bold hover:bg-surface-container">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
