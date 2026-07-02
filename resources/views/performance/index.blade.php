@extends('layouts.app')

@section('title', 'الأداء')

@section('content')
    <div class="space-y-8">
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
            <div>
                <p class="font-label text-xs font-bold uppercase tracking-[0.25em] text-tertiary">Performance archive</p>
                <h2 class="mt-2 font-headline text-display-lg font-bold text-primary">إدارة الأداء</h2>
                <p class="mt-2 max-w-2xl text-on-surface-variant">متابعة مؤشرات الأداء، دورات التقييم، والأهداف على مستوى الشركات والأقسام.</p>
            </div>
            <button class="stitch-btn-primary flex items-center gap-2 px-6 py-3">
                <span class="material-symbols-outlined">add_chart</span>
                <span>بدء دورة تقييم</span>
            </button>
        </div>

        <div class="grid gap-5 md:grid-cols-4">
            @foreach([
                ['متوسط الأداء', '4.2', 'star'],
                ['تقييمات مكتملة', '74%', 'task_alt'],
                ['أهداف نشطة', '128', 'track_changes'],
                ['بحاجة متابعة', '9', 'priority_high'],
            ] as [$label, $value, $icon])
                <div class="rounded-2xl bg-surface-container-lowest p-6 shadow-[0_18px_36px_rgba(27,28,29,0.035)]">
                    <div class="mb-6 flex h-12 w-12 items-center justify-center rounded-xl bg-tertiary-fixed text-on-tertiary-fixed">
                        <span class="material-symbols-outlined">{{ $icon }}</span>
                    </div>
                    <p class="text-sm text-on-surface-variant">{{ $label }}</p>
                    <h3 class="mt-1 font-headline text-3xl font-bold text-primary">{{ $value }}</h3>
                </div>
            @endforeach
        </div>

        <section class="grid gap-6 xl:grid-cols-3">
            <div class="rounded-2xl bg-surface-container-lowest p-6 shadow-[0_18px_36px_rgba(27,28,29,0.035)] xl:col-span-2">
                <h3 class="font-headline text-2xl font-bold text-primary">توزيع التقييمات</h3>
                <div class="mt-8 flex h-72 items-end justify-around gap-5">
                    @foreach([92, 78, 64, 42, 24] as $bar)
                        <div class="flex flex-1 flex-col items-center gap-3">
                            <div class="flex h-full w-full max-w-20 items-end rounded-t-xl bg-surface-container">
                                <div class="w-full rounded-t-xl bg-primary" style="height: {{ $bar }}%"></div>
                            </div>
                            <span class="text-xs text-on-surface-variant">{{ $bar }}%</span>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="rounded-2xl bg-primary p-6 text-on-primary shadow-[0_18px_36px_rgba(27,28,29,0.08)]">
                <span class="material-symbols-outlined text-4xl">auto_awesome</span>
                <h3 class="mt-5 font-headline text-2xl font-bold">رؤية إدارية</h3>
                <p class="mt-3 text-sm text-white/80">الأقسام ذات الإنجاز المنخفض تحتاج جلسات متابعة أسبوعية وربط الأهداف بخطط التدريب.</p>
            </div>
        </section>
    </div>
@endsection
