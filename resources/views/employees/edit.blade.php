@extends('layouts.app')

@section('title', 'تعديل بيانات موظف')

@section('content')
    <div class="mx-auto max-w-4xl">
        <div class="overflow-hidden rounded-3xl bg-surface shadow-2xl ring-1 ring-outline-variant">
            <div class="flex items-center justify-between bg-primary p-6 text-on-primary">
                <div>
                    <h2 class="text-2xl font-bold">تعديل بيانات الموظف</h2>
                    <p class="mt-1 text-sm opacity-80">{{ $employee->name_ar }} — {{ $employee->employee_code }}</p>
                </div>
                <a href="{{ route('employees.show', $employee) }}" class="rounded-full p-2 hover:bg-white/20">إغلاق</a>
            </div>
            <form method="POST" action="{{ route('employees.update', $employee) }}" class="space-y-8 p-8">
                @csrf
                @method('PUT')
                @include('employees._form')
                <div class="flex gap-4 pt-4">
                    <button class="stitch-btn-primary flex-1 py-4">حفظ التعديلات</button>
                    <a href="{{ route('employees.show', $employee) }}" class="rounded-xl border border-outline px-8 py-4 font-bold hover:bg-surface-container">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
