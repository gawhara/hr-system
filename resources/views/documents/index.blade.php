@extends('layouts.app')

@section('title', 'متتبع الوثائق')

@section('content')
    <div class="space-y-8">
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
            <div>
                <h2 class="text-display-lg font-bold text-primary">متتبع انتهاء صلاحية الوثائق والتوقعات</h2>
                <p class="mt-1 text-on-surface-variant">مراقبة الإقامات وجوازات السفر والعقود مع توقعات التجديد القادمة.</p>
            </div>
            <div class="flex gap-sm">
                <button class="flex items-center gap-2 rounded-xl bg-secondary-container px-5 py-3 font-bold text-on-secondary-container shadow-sm transition-all hover:opacity-90 active:scale-95">
                    <span class="material-symbols-outlined">file_download</span>
                    <span>تصدير Excel</span>
                </button>
                <button class="flex items-center gap-2 rounded-xl bg-primary px-5 py-3 font-bold text-on-primary shadow-sm transition-all hover:bg-primary-container active:scale-95">
                    <span class="material-symbols-outlined">notifications_active</span>
                    <span>تنبيه التجديد</span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-md md:grid-cols-2 xl:grid-cols-4">
            @foreach([
                ['كل الوثائق', $metrics['total'], 'description', 'bg-primary-fixed text-primary', 'المستندات المسجلة'],
                ['منتهية', $metrics['expired'], 'dangerous', 'bg-error-container text-error', 'تحتاج إجراء فوري'],
                ['خلال 15 يوم', $metrics['urgent'], 'timer', 'bg-orange-100 text-orange-700', 'أولوية عالية'],
                ['خلال 45 يوم', $metrics['soon'], 'event_upcoming', 'bg-secondary-fixed text-secondary', 'تجديد قريب'],
            ] as [$label, $value, $icon, $tone, $caption])
                <div class="relative overflow-hidden rounded-xl border border-outline-variant bg-surface-container-lowest p-md shadow-sm transition-shadow hover:shadow-md">
                    <div class="absolute -bottom-3 -left-3 text-7xl opacity-10">
                        <span class="material-symbols-outlined">{{ $icon }}</span>
                    </div>
                    <div class="relative flex items-start justify-between">
                        <div>
                            <p class="text-body-sm text-on-surface-variant">{{ $label }}</p>
                            <h3 class="mt-2 text-display-lg font-bold text-primary">{{ number_format($value) }}</h3>
                            <p class="mt-1 text-xs text-outline">{{ $caption }}</p>
                        </div>
                        <div class="rounded-lg p-3 {{ $tone }}">
                            <span class="material-symbols-outlined">{{ $icon }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-md xl:grid-cols-3">
            <section class="rounded-xl border border-outline-variant bg-surface-container-lowest p-md shadow-sm xl:col-span-2">
                <div class="mb-md flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <h3 class="text-headline-md font-bold text-primary">التوقعات القادمة</h3>
                        <p class="text-body-sm text-on-surface-variant">قراءة سريعة للمستندات التي ستنتهي خلال الفترات القريبة.</p>
                    </div>
                    <div class="rounded-full bg-surface-container-low px-4 py-2 text-xs font-bold text-primary">Document Forecast</div>
                </div>
                <div class="grid gap-4 md:grid-cols-3">
                    @foreach([
                        ['15 يوم', $forecast['15'], 'bg-error'],
                        ['30 يوم', $forecast['30'], 'bg-orange-500'],
                        ['45 يوم', $forecast['45'], 'bg-secondary'],
                    ] as [$label, $value, $bar])
                        @php
                            $height = $metrics['total'] > 0
                                ? max(12, min(100, round(($value / $metrics['total']) * 100)))
                                : 12;
                        @endphp
                        <div class="rounded-xl bg-surface-container-low p-5">
                            <div class="flex h-36 items-end justify-center rounded-lg bg-white p-4">
                                <div class="chart-bar-transition w-16 rounded-t-lg {{ $bar }}" style="height: {{ $height }}%"></div>
                            </div>
                            <div class="mt-4 flex items-center justify-between">
                                <span class="font-bold text-on-surface">{{ $label }}</span>
                                <span class="text-xl font-black text-primary">{{ $value }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <aside class="rounded-xl border border-outline-variant bg-primary p-md text-on-primary shadow-sm">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-white/15">
                    <span class="material-symbols-outlined">auto_awesome</span>
                </div>
                <h3 class="mt-5 text-headline-md font-bold">أولوية المتابعة</h3>
                <p class="mt-2 text-sm text-white/80">ابدأ بالوثائق المنتهية ثم وثائق آخر 15 يوم لضمان عدم توقف خدمات الموظفين.</p>
                <div class="mt-6 space-y-3">
                    <div class="rounded-lg bg-white/10 p-3 text-sm">منتهية: {{ number_format($metrics['expired']) }}</div>
                    <div class="rounded-lg bg-white/10 p-3 text-sm">عاجلة: {{ number_format($metrics['urgent']) }}</div>
                    <div class="rounded-lg bg-white/10 p-3 text-sm">سليمة: {{ number_format($metrics['healthy']) }}</div>
                </div>
            </aside>
        </div>

        <section class="overflow-hidden rounded-xl border border-outline-variant bg-surface-container-lowest shadow-sm">
            <div class="flex flex-col justify-between gap-4 border-b border-outline-variant bg-surface-container-low p-md md:flex-row md:items-center">
                <h3 class="flex items-center gap-2 text-title-sm font-bold text-primary">
                    <span class="material-symbols-outlined">table_chart</span>
                    سجل الوثائق
                </h3>
                <div class="flex gap-2">
                    <div class="relative">
                        <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-sm text-on-surface-variant">search</span>
                        <input class="w-64 rounded-lg border border-outline-variant bg-white py-2 pl-3 pr-9 text-body-sm focus:border-primary focus:ring-primary" placeholder="بحث باسم الموظف..." type="text">
                    </div>
                    <button class="flex items-center gap-2 rounded-lg border border-outline-variant px-4 py-2 transition-colors hover:bg-surface-container">
                        <span class="material-symbols-outlined">filter_list</span>
                        <span class="text-body-sm">فلترة</span>
                    </button>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-right text-body-sm">
                    <thead class="bg-surface-container-highest/30 text-on-surface-variant">
                        <tr>
                            <th class="border-b border-outline-variant px-md py-4 font-bold">الموظف</th>
                            <th class="border-b border-outline-variant px-md py-4 font-bold">الشركة</th>
                            <th class="border-b border-outline-variant px-md py-4 font-bold">نوع الوثيقة</th>
                            <th class="border-b border-outline-variant px-md py-4 font-bold">تاريخ الانتهاء</th>
                            <th class="border-b border-outline-variant px-md py-4 font-bold">الأيام المتبقية</th>
                            <th class="border-b border-outline-variant px-md py-4 font-bold">الحالة</th>
                            <th class="border-b border-outline-variant px-md py-4 text-center font-bold">الإجراء</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/50">
                        @forelse($visibleDocuments as $row)
                            @php
                                $status = [
                                    'expired' => ['منتهية', 'bg-error-container text-error'],
                                    'urgent' => ['عاجلة', 'bg-orange-100 text-orange-700'],
                                    'soon' => ['قريبة', 'bg-secondary-fixed text-secondary'],
                                    'healthy' => ['سليمة', 'bg-green-100 text-green-700'],
                                ][$row['status']];
                            @endphp
                            <tr class="transition-colors hover:bg-surface-container-high/20">
                                <td class="px-md py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-fixed font-bold text-primary">{{ mb_substr($row['employee']->name_ar, 0, 1) }}</div>
                                        <div>
                                            <div class="font-bold text-primary">{{ $row['employee']->name_ar }}</div>
                                            <div class="text-[11px] text-on-surface-variant">#{{ $row['employee']->employee_code }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-md py-4">{{ $row['employee']->company?->name_ar }}</td>
                                <td class="px-md py-4">
                                    <span class="inline-flex items-center gap-2">
                                        <span class="material-symbols-outlined text-sm text-primary">{{ $row['icon'] }}</span>
                                        {{ $row['type'] }}
                                    </span>
                                </td>
                                <td class="px-md py-4">{{ $row['expires_on']->format('Y-m-d') }}</td>
                                <td class="px-md py-4 font-mono font-bold {{ $row['days_left'] < 0 ? 'text-error' : 'text-primary' }}">{{ $row['days_left'] }}</td>
                                <td class="px-md py-4"><span class="rounded-full px-2 py-1 text-[11px] font-bold {{ $status[1] }}">{{ $status[0] }}</span></td>
                                <td class="px-md py-4 text-center">
                                    <a href="{{ route('employees.show', $row['employee']) }}" class="inline-flex rounded-full p-2 transition-colors hover:bg-surface-container">
                                        <span class="material-symbols-outlined text-primary">visibility</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-6 py-10 text-center text-on-surface-variant">لا توجد وثائق مسجلة.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
