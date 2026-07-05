@extends('layouts.app')

@section('title', 'موظف جديد')

@section('content')
    <div class="mx-auto max-w-6xl space-y-6">
        <section class="overflow-hidden rounded-3xl border border-outline-variant/50 bg-white shadow-[0_16px_38px_rgba(25,28,30,0.05)]">
            <div class="flex flex-col gap-5 bg-gradient-to-br from-[#1a2b4b] via-[#243b63] to-[#0f1d33] p-6 text-white md:flex-row md:items-center md:justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl border border-white/15 bg-white/12">
                        <span class="material-symbols-outlined text-3xl">person_add</span>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-white/60">Employee Profile</p>
                        <h2 class="mt-1 text-2xl font-black">إنشاء ملف موظف جديد</h2>
                        <p class="mt-1 text-sm text-white/72">أدخل بيانات الموظف الأساسية والإدارية والمالية دون تغيير هيكل النظام.</p>
                    </div>
                </div>
                <a href="{{ route('employees.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/15 bg-white/10 px-4 py-2 text-sm font-bold text-white transition hover:bg-white/18">
                    <span class="material-symbols-outlined text-lg">close</span>
                    <span>إغلاق</span>
                </a>
            </div>
        </section>

        <form method="POST" action="{{ route('employees.store') }}" class="space-y-6">
            @csrf
            @include('employees._form')

            <div class="sticky bottom-6 z-20 rounded-2xl border border-outline-variant/50 bg-white/92 p-4 shadow-[0_18px_44px_rgba(25,28,30,0.10)] backdrop-blur-xl">
                <div class="flex flex-col gap-3 sm:flex-row">
                    <button class="stitch-btn-primary flex-1 px-6 py-4">حفظ الموظف</button>
                    <a href="{{ route('employees.index') }}" class="inline-flex items-center justify-center rounded-xl border border-outline-variant px-8 py-4 font-bold text-on-surface-variant transition hover:bg-surface-container">إلغاء</a>
                </div>
            </div>
        </form>
    </div>
@endsection
