@extends('layouts.app')

@section('title', 'موظف جديد')

@section('content')
    <div class="mx-auto max-w-4xl">
        <div class="overflow-hidden rounded-3xl bg-surface shadow-2xl ring-1 ring-outline-variant">
            <div class="flex items-center justify-between bg-primary p-6 text-on-primary">
                <h2 class="text-2xl font-bold">إنشاء ملف موظف جديد</h2>
                <a href="{{ route('employees.index') }}" class="rounded-full p-2 hover:bg-white/20">إغلاق</a>
            </div>
            <form method="POST" action="{{ route('employees.store') }}" class="space-y-8 p-8">
                @csrf
                @include('employees._form')
                <div class="flex gap-4 pt-4">
                    <button class="stitch-btn-primary flex-1 py-4">حفظ الموظف</button>
                    <a href="{{ route('employees.index') }}" class="rounded-xl border border-outline px-8 py-4 font-bold hover:bg-surface-container">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
