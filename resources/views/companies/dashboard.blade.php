@extends('layouts.app')

@section('title', $company->name_ar)

@section('content')
    @php
        $theme = $dashboard['theme'];
        $headcount = max((int) $dashboard['headcount'], 1);
        $nonSaudiPercent = round(((int) $dashboard['non_saudis'] / $headcount) * 100);
        $malePercent = round(((int) $genderChart['male'] / $headcount) * 100);
        $femalePercent = round(((int) $genderChart['female'] / $headcount) * 100);
        $nitaqatBand = $dashboard['nitaqat_band'];
    @endphp

    <div class="mx-auto max-w-7xl space-y-6">
        <div class="overflow-hidden rounded-3xl bg-surface-container-lowest shadow-[0_22px_46px_rgba(17,24,39,0.07)]">
            <div class="h-2" style="background: linear-gradient(to left, {{ $theme['from'] }}, {{ $theme['to'] }});"></div>
            <div class="flex flex-col gap-4 p-6 md:flex-row md:items-center md:justify-between">
                <div class="flex items-center gap-5">
                    <div class="flex h-24 w-28 items-center justify-center rounded-3xl bg-white p-4 shadow-[0_14px_30px_rgba(17,24,39,0.08)] ring-1" style="--tw-ring-color: {{ $theme['ring'] }};">
                        <img src="{{ asset($dashboard['logo']) }}" alt="{{ $company->name_ar }}" class="max-h-16 max-w-full object-contain">
                    </div>
                    <div>
                        <a href="{{ route('dashboard') }}" class="mb-3 inline-flex items-center gap-2 text-sm font-bold" style="color: {{ $theme['text'] }};">
                            <span class="material-symbols-outlined text-base">arrow_forward</span>
                            العودة للوحة الرئيسية
                        </a>
                        <h1 class="font-headline text-3xl font-bold" style="color: {{ $theme['text'] }};">{{ $company->name_ar }}</h1>
                        <p class="mt-1 text-sm font-semibold uppercase tracking-wide text-on-surface-variant">{{ $company->name }}</p>
                    </div>
                </div>

                <div class="rounded-3xl px-6 py-4" style="background: {{ $nitaqatBand['background'] }}; color: {{ $nitaqatBand['text'] }};">
                    <span class="block text-xs font-bold opacity-70">نسبة التوطين</span>
                    <strong class="mt-1 block font-headline text-4xl">{{ $localizationPercent }}%</strong>
                    <span class="mt-2 inline-flex rounded-full bg-white/75 px-3 py-1 text-xs font-bold">{{ $nitaqatBand['label'] }}</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
            @foreach([
                ['الموظفون', $dashboard['headcount'], 'groups'],
                ['الموظفون النشطون', $activeEmployees, 'verified_user'],
                ['الفروع', $branchesCount, 'account_tree'],
                ['الأقسام', $departmentsCount, 'category'],
            ] as [$label, $value, $icon])
                <div class="rounded-3xl bg-surface-container-lowest p-5 shadow-[0_18px_36px_rgba(17,24,39,0.05)]">
                    <span class="material-symbols-outlined" style="color: {{ $theme['text'] }};">{{ $icon }}</span>
                    <span class="mt-5 block text-sm text-on-surface-variant">{{ $label }}</span>
                    <strong class="mt-2 block font-headline text-3xl text-on-surface">{{ number_format($value) }}</strong>
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <section class="rounded-3xl bg-surface-container-lowest p-6 shadow-[0_18px_36px_rgba(17,24,39,0.05)]">
                <h2 class="font-headline text-2xl font-bold" style="color: {{ $theme['text'] }};">توزيع الموظفين</h2>
                <div class="mt-6 grid grid-cols-[180px_1fr] items-center gap-6">
                    <div class="grid h-44 w-44 place-items-center rounded-full" style="background: conic-gradient({{ $nitaqatBand['from'] }} 0 {{ $localizationPercent }}%, #e5e7eb {{ $localizationPercent }}% 100%);">
                        <div class="grid h-28 w-28 place-items-center rounded-full bg-white text-center">
                            <span class="block text-xs text-on-surface-variant">التوطين</span>
                            <strong class="font-headline text-3xl" style="color: {{ $nitaqatBand['text'] }};">{{ $localizationPercent }}%</strong>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <div class="mb-2 flex justify-between text-sm font-bold">
                                <span>السعوديون</span>
                                <span>{{ number_format($dashboard['saudis']) }}</span>
                            </div>
                            <div class="h-3 rounded-full bg-surface-container">
                                <div class="h-3 rounded-full" style="width: {{ $localizationPercent }}%; background: linear-gradient(to left, {{ $nitaqatBand['from'] }}, {{ $nitaqatBand['to'] }});"></div>
                            </div>
                        </div>
                        <div>
                            <div class="mb-2 flex justify-between text-sm font-bold">
                                <span>غير السعوديين</span>
                                <span>{{ number_format($dashboard['non_saudis']) }}</span>
                            </div>
                            <div class="h-3 rounded-full bg-surface-container">
                                <div class="h-3 rounded-full" style="width: {{ $nonSaudiPercent }}%; background: {{ $theme['muted'] }};"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl bg-surface-container-lowest p-6 shadow-[0_18px_36px_rgba(17,24,39,0.05)]">
                <h2 class="font-headline text-2xl font-bold" style="color: {{ $theme['text'] }};">الرواتب والتنبيهات</h2>
                <div class="mt-6 grid grid-cols-2 gap-4">
                    <div class="rounded-2xl p-5" style="background: {{ $theme['soft'] }};">
                        <span class="block text-sm text-on-surface-variant">صافي الرواتب</span>
                        <strong class="mt-2 block font-headline text-3xl" style="color: {{ $theme['text'] }};">{{ number_format($dashboard['monthly_payroll'], 0) }}</strong>
                    </div>
                    <div class="rounded-2xl bg-surface-container-low p-5">
                        <span class="block text-sm text-on-surface-variant">متوسط الراتب الأساسي</span>
                        <strong class="mt-2 block font-headline text-3xl text-on-surface">{{ number_format($averageSalary, 0) }}</strong>
                    </div>
                    <div class="rounded-2xl bg-error-container/55 p-5">
                        <span class="block text-sm text-on-surface-variant">إجازات معلقة</span>
                        <strong class="mt-2 block font-headline text-3xl text-error">{{ number_format($dashboard['pending_leaves']) }}</strong>
                    </div>
                    <div class="rounded-2xl p-5" style="background: {{ $theme['soft'] }};">
                        <span class="block text-sm text-on-surface-variant">تنبيهات وثائق</span>
                        <strong class="mt-2 block font-headline text-3xl" style="color: {{ $theme['text'] }};">{{ number_format($dashboard['document_alerts']) }}</strong>
                    </div>
                </div>
            </section>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <section class="rounded-3xl bg-surface-container-lowest p-6 shadow-[0_18px_36px_rgba(17,24,39,0.05)]">
                <h2 class="font-headline text-2xl font-bold" style="color: {{ $theme['text'] }};">الأقسام الأعلى عددًا</h2>
                <div class="mt-6 space-y-4">
                    @forelse($departmentChart as $department)
                        <div>
                            <div class="mb-2 flex justify-between text-sm font-bold">
                                <span>{{ $department['name'] }}</span>
                                <span>{{ number_format($department['total']) }}</span>
                            </div>
                            <div class="h-3 rounded-full bg-surface-container">
                                <div class="h-3 rounded-full" style="width: {{ $department['percent'] }}%; background: linear-gradient(to left, {{ $theme['from'] }}, {{ $theme['to'] }});"></div>
                            </div>
                        </div>
                    @empty
                        <p class="rounded-2xl bg-surface-container-low p-5 text-center text-on-surface-variant">لا توجد بيانات أقسام لهذه الشركة.</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-3xl bg-surface-container-lowest p-6 shadow-[0_18px_36px_rgba(17,24,39,0.05)]">
                <h2 class="font-headline text-2xl font-bold" style="color: {{ $theme['text'] }};">مؤشرات إضافية</h2>
                <div class="mt-6 space-y-4">
                    <div>
                        <div class="mb-2 flex justify-between text-sm font-bold">
                            <span>ذكور</span>
                            <span>{{ number_format($genderChart['male']) }}</span>
                        </div>
                        <div class="h-3 rounded-full bg-surface-container">
                            <div class="h-3 rounded-full" style="width: {{ $malePercent }}%; background: {{ $theme['from'] }};"></div>
                        </div>
                    </div>
                    <div>
                        <div class="mb-2 flex justify-between text-sm font-bold">
                            <span>إناث</span>
                            <span>{{ number_format($genderChart['female']) }}</span>
                        </div>
                        <div class="h-3 rounded-full bg-surface-container">
                            <div class="h-3 rounded-full" style="width: {{ $femalePercent }}%; background: {{ $theme['to'] }};"></div>
                        </div>
                    </div>
                    <div class="rounded-2xl p-5 text-white" style="background: {{ $theme['dark'] }};">
                        <span class="block text-sm text-white/65">حالة الصفحة</span>
                        <strong class="mt-2 block text-xl">لوحة تفصيلية خاصة بالشركة</strong>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
