@extends('layouts.app')

@section('title', 'التوظيف')

@section('content')
    <div class="space-y-8">
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
            <div>
                <p class="font-label text-xs font-bold uppercase tracking-[0.25em] text-tertiary">Talent acquisition</p>
                <h2 class="mt-2 font-headline text-display-lg font-bold text-primary">إدارة التوظيف</h2>
                <p class="mt-2 max-w-2xl text-on-surface-variant">لوحة تحريرية لمتابعة الطلبات الوظيفية والمرشحين والمقابلات القادمة.</p>
            </div>
            <button class="stitch-btn-primary flex items-center gap-2 px-6 py-3">
                <span class="material-symbols-outlined">add</span>
                <span>طلب وظيفة جديد</span>
            </button>
        </div>

        <div class="grid gap-5 md:grid-cols-4">
            @foreach([
                ['الوظائف المفتوحة', '12', 'work'],
                ['المرشحون', '86', 'person_search'],
                ['مقابلات هذا الأسبوع', '18', 'event'],
                ['عروض قيد الاعتماد', '6', 'approval'],
            ] as [$label, $value, $icon])
                <div class="rounded-2xl bg-surface-container-lowest p-6 shadow-[0_18px_36px_rgba(27,28,29,0.035)]">
                    <div class="mb-6 flex h-12 w-12 items-center justify-center rounded-xl bg-primary-fixed text-primary">
                        <span class="material-symbols-outlined">{{ $icon }}</span>
                    </div>
                    <p class="text-sm text-on-surface-variant">{{ $label }}</p>
                    <h3 class="mt-1 font-headline text-3xl font-bold text-primary">{{ $value }}</h3>
                </div>
            @endforeach
        </div>

        <section class="rounded-2xl bg-surface-container-lowest p-6 shadow-[0_18px_36px_rgba(27,28,29,0.035)]">
            <h3 class="font-headline text-2xl font-bold text-primary">مسار المرشحين</h3>
            <div class="mt-6 grid gap-4 md:grid-cols-4">
                @foreach(['استقبال السير', 'فرز أولي', 'مقابلة', 'عرض وظيفي'] as $step)
                    <div class="rounded-xl bg-surface-container-low p-5">
                        <p class="font-bold text-on-surface">{{ $step }}</p>
                        <div class="mt-4 h-2 rounded-full bg-surface-container-highest">
                            <div class="h-full rounded-full bg-primary" style="width: {{ rand(35, 90) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
@endsection
