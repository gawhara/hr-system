@extends('layouts.app')

@section('title', 'حاسبة نطاقات')

@php
    $bandStyles = [
        'platinum' => ['label' => 'بلاتيني', 'box' => 'bg-blue-50 text-blue-800 ring-blue-200', 'bar' => 'from-[#1a2b4b] to-[#d4af37]'],
        'high_green' => ['label' => 'أخضر مرتفع', 'box' => 'bg-emerald-50 text-emerald-800 ring-emerald-200', 'bar' => 'from-emerald-700 to-emerald-400'],
        'low_green' => ['label' => 'أخضر منخفض', 'box' => 'bg-lime-50 text-lime-800 ring-lime-200', 'bar' => 'from-lime-600 to-lime-300'],
        'yellow' => ['label' => 'أصفر', 'box' => 'bg-amber-50 text-amber-800 ring-amber-200', 'bar' => 'from-amber-500 to-yellow-300'],
        'red' => ['label' => 'أحمر', 'box' => 'bg-red-50 text-red-800 ring-red-200', 'bar' => 'from-red-600 to-rose-400'],
        'unknown' => ['label' => 'غير محدد', 'box' => 'bg-surface-container text-on-surface ring-outline-variant', 'bar' => 'from-slate-500 to-slate-300'],
    ];
    $currentBand = $bandStyles[$result['band'] ?? 'unknown'] ?? $bandStyles['unknown'];
@endphp

@section('content')
    <div class="mx-auto max-w-6xl space-y-6">
        <section class="overflow-hidden rounded-3xl bg-surface-container-lowest shadow-[0_22px_46px_rgba(17,24,39,0.07)] ring-1 ring-outline-variant/15">
            <div class="h-2 bg-gradient-to-l from-[#0b0b0c] to-[#f59e0b]"></div>
            <div class="grid gap-6 p-6 lg:grid-cols-[1fr_360px] lg:p-8">
                <div>
                    <p class="font-label text-xs font-bold uppercase tracking-[0.22em] text-tertiary">Nitaqat Calculator</p>
                    <h1 class="mt-2 font-headline text-3xl font-black text-primary">حاسبة نطاقات</h1>
                    <p class="mt-3 max-w-3xl text-sm leading-7 text-on-surface-variant">
                        تحسب النسبة الموزونة للتوطين من بيانات موظفي الشركة الحالية، مع حفظ سجل للحساب للمراجعة. الأهداف الافتراضية قابلة للتحديث ويجب مطابقتها مع قوى أو الدليل الرسمي قبل الاعتماد التشغيلي.
                    </p>
                </div>
                <div class="rounded-2xl bg-[#111827] p-5 text-white">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined rounded-xl bg-white/10 p-3 text-3xl">verified_user</span>
                        <div>
                            <div class="text-sm text-white/60">مصدر الحساب</div>
                            <div class="font-headline text-xl font-bold">بيانات الموظفين النشطة</div>
                        </div>
                    </div>
                    <p class="mt-4 text-xs leading-6 text-white/60">لا يتم إرسال البيانات إلى أي جهة خارجية. يتم حفظ نسخة مختصرة من المدخلات داخلياً لأغراض التدقيق.</p>
                </div>
            </div>
        </section>

        <section class="rounded-3xl bg-surface-container-lowest p-6 shadow-[0_18px_36px_rgba(27,28,29,0.035)] ring-1 ring-outline-variant/15">
            <form method="POST" action="{{ route('nitaqat.calculate') }}" class="grid gap-4 lg:grid-cols-[1fr_1fr_auto] lg:items-end">
                @csrf
                <label class="block">
                    <span class="mb-2 block text-sm font-bold text-on-surface">الشركة</span>
                    <select name="company_id" class="w-full rounded-2xl border-outline-variant bg-white">
                        @foreach($companies as $availableCompany)
                            <option value="{{ $availableCompany->id }}" @selected((int) old('company_id', $selectedCompanyId) === $availableCompany->id)>
                                {{ $availableCompany->name_ar }} - {{ $availableCompany->name_en }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-bold text-on-surface">النشاط الاقتصادي</span>
                    <select name="economic_activity_id" class="w-full rounded-2xl border-outline-variant bg-white">
                        @forelse($activities as $availableActivity)
                            <option value="{{ $availableActivity->id }}" @selected((int) old('economic_activity_id', $activity?->id) === $availableActivity->id)>
                                {{ $availableActivity->name_ar }} - الهدف {{ number_format($availableActivity->currentTargetPercentage() * 100, 2) }}%
                            </option>
                        @empty
                            <option value="">لا توجد أنشطة مضافة</option>
                        @endforelse
                    </select>
                </label>

                <button class="inline-flex h-12 items-center justify-center gap-2 rounded-2xl bg-primary px-6 font-bold text-white shadow-[0_16px_30px_rgba(109,40,217,0.24)] transition hover:-translate-y-0.5">
                    <span class="material-symbols-outlined">calculate</span>
                    احسب النطاق
                </button>
            </form>

            @error('company_id')
                <p class="mt-3 text-sm font-bold text-error">{{ $message }}</p>
            @enderror
            @error('economic_activity_id')
                <p class="mt-3 text-sm font-bold text-error">{{ $message }}</p>
            @enderror
        </section>

        @if($result)
            <section class="grid gap-5 lg:grid-cols-4">
                <div class="rounded-3xl bg-surface-container-lowest p-6 shadow-[0_18px_36px_rgba(27,28,29,0.035)]">
                    <span class="text-sm text-on-surface-variant">إجمالي الموظفين</span>
                    <strong class="mt-3 block font-headline text-4xl text-primary">{{ number_format($result['total_employees']) }}</strong>
                </div>
                <div class="rounded-3xl bg-surface-container-lowest p-6 shadow-[0_18px_36px_rgba(27,28,29,0.035)]">
                    <span class="text-sm text-on-surface-variant">السعوديون</span>
                    <strong class="mt-3 block font-headline text-4xl text-primary">{{ number_format($result['total_saudis_headcount']) }}</strong>
                </div>
                <div class="rounded-3xl bg-surface-container-lowest p-6 shadow-[0_18px_36px_rgba(27,28,29,0.035)]">
                    <span class="text-sm text-on-surface-variant">النسبة المحققة</span>
                    <strong class="mt-3 block font-headline text-4xl text-primary">{{ number_format($result['achieved_percentage'], 2) }}%</strong>
                </div>
                <div class="rounded-3xl p-6 shadow-[0_18px_36px_rgba(27,28,29,0.035)] ring-1 {{ $currentBand['box'] }}">
                    <span class="text-sm opacity-75">النطاق</span>
                    <strong class="mt-3 block font-headline text-3xl">{{ $currentBand['label'] }}</strong>
                </div>
            </section>

            <section class="rounded-3xl bg-surface-container-lowest p-6 shadow-[0_18px_36px_rgba(27,28,29,0.035)] ring-1 ring-outline-variant/15">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="font-headline text-2xl font-bold text-primary">{{ $company?->name_ar }}</h2>
                        <p class="mt-1 text-sm text-on-surface-variant">{{ $activity?->name_ar }} - الهدف المطلوب {{ number_format($result['required_percentage'], 2) }}%</p>
                    </div>
                    <div class="rounded-full bg-surface-container px-4 py-2 text-sm font-bold text-on-surface">
                        الوزن السعودي: {{ number_format($result['total_weighted_saudis'], 2) }}
                    </div>
                </div>

                <div class="mt-6">
                    <div class="mb-2 flex justify-between text-xs font-bold text-on-surface-variant">
                        <span>المحقق {{ number_format($result['achieved_percentage'], 2) }}%</span>
                        <span>المطلوب {{ number_format($result['required_percentage'], 2) }}%</span>
                    </div>
                    <div class="h-4 overflow-hidden rounded-full bg-surface-container">
                        <div class="h-full rounded-full bg-gradient-to-l {{ $currentBand['bar'] }}" style="width: {{ min($result['achieved_percentage'], 100) }}%"></div>
                    </div>
                </div>

                <div class="mt-6 rounded-2xl bg-[#111827] p-5 text-white">
                    @if($result['additional_saudis_needed'] > 0)
                        تحتاج الشركة تقريباً إلى {{ number_format($result['additional_saudis_needed']) }} موظف سعودي إضافي بوزن كامل للوصول إلى الحد المستهدف.
                    @else
                        الشركة محققة للنسبة المطلوبة أو أعلى حسب الإعدادات الحالية.
                    @endif
                </div>
            </section>
        @endif
    </div>
@endsection
