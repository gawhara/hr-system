@extends('layouts.app')

@section('title', 'أجهزة البصمة')

@section('content')
    <div class="space-y-8">
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
            <div>
                <p class="font-label text-xs font-bold uppercase tracking-[0.25em] text-tertiary">Biometric Devices</p>
                <h2 class="mt-2 font-headline text-display-lg font-bold text-primary">أجهزة البصمة</h2>
                <p class="mt-2 max-w-3xl text-on-surface-variant">
                    إدارة أجهزة الحضور لجميع شركات المجموعة من مكان واحد. يمكن إضافة أي جهاز يصل إليه الخادم عبر
                    الشبكة المحلية أو VPN أو IP ثابت أو اسم DDNS — أجهزة ZKTeco تتصل عبر منفذ
                    <span dir="ltr" class="font-bold">UDP 4370</span>
                    (تأكد من توجيه المنفذ في أجهزة الراوتر عند استخدام DDNS).
                </p>
            </div>
        </div>

        @if(session('status'))
            <div class="rounded-2xl border border-green-300 bg-green-50 p-4 text-sm font-bold text-green-800">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="rounded-2xl border border-red-300 bg-red-50 p-4 text-sm font-bold text-red-800">{{ $errors->first() }}</div>
        @endif

        <details class="glass-card rounded-2xl p-6" {{ $errors->any() ? 'open' : '' }}>
            <summary class="flex cursor-pointer list-none items-center gap-3 font-headline text-xl font-bold text-primary">
                <span class="material-symbols-outlined">add_circle</span>
                <span>إضافة جهاز جديد</span>
            </summary>
            <form method="POST" action="{{ route('devices.store') }}" class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @csrf
                <div>
                    <label class="mb-2 block text-sm font-bold text-on-surface-variant">الشركة *</label>
                    <select name="company_id" id="device-company" class="stitch-input w-full p-3" required>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" @selected((int) old('company_id') === $company->id)>{{ $company->name_ar }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-bold text-on-surface-variant">الفرع</label>
                    <select name="branch_id" id="device-branch" class="stitch-input w-full p-3">
                        <option value="">—</option>
                        @foreach($companies as $company)
                            @foreach($company->branches as $branch)
                                <option value="{{ $branch->id }}" data-company="{{ $company->id }}" @selected((int) old('branch_id') === $branch->id)>{{ $branch->name_ar }}</option>
                            @endforeach
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-bold text-on-surface-variant">اسم الجهاز *</label>
                    <input name="name_ar" value="{{ old('name_ar') }}" class="stitch-input w-full p-3" placeholder="بصمة الفرع الرئيسي" required>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-bold text-on-surface-variant">العنوان (IP أو DDNS) *</label>
                    <input name="host" value="{{ old('host') }}" class="stitch-input w-full p-3" dir="ltr" placeholder="192.168.1.201 أو branch1.dvrdns.org" required>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-bold text-on-surface-variant">المنفذ *</label>
                    <input name="port" type="number" value="{{ old('port', 4370) }}" class="stitch-input w-full p-3" dir="ltr" min="1" max="65535" required>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-bold text-on-surface-variant">مفتاح الاتصال (Comm Key)</label>
                    <input name="comm_key" type="number" value="{{ old('comm_key', 0) }}" class="stitch-input w-full p-3" dir="ltr" min="0">
                </div>
                <div class="sm:col-span-2 xl:col-span-3">
                    <button class="stitch-btn-primary px-8 py-3">حفظ الجهاز</button>
                </div>
            </form>
        </details>

        @foreach($companies as $company)
            <section class="glass-card rounded-2xl p-6">
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="font-headline text-xl font-bold text-on-surface">{{ $company->name_ar }}</h3>
                    <span class="rounded-full bg-surface-container px-3 py-1 text-xs font-bold text-on-surface-variant">
                        {{ ($devicesByCompany[$company->id] ?? collect())->count() }} جهاز
                    </span>
                </div>

                @php $companyDevices = $devicesByCompany[$company->id] ?? collect(); @endphp

                @if($companyDevices->isEmpty())
                    <p class="rounded-xl bg-surface-container-low p-4 text-sm text-on-surface-variant">لا توجد أجهزة مسجلة لهذه الشركة بعد.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-right text-sm">
                            <thead class="bg-surface-container-low text-on-surface-variant">
                                <tr>
                                    <th class="px-4 py-3 font-bold">الجهاز</th>
                                    <th class="px-4 py-3 font-bold">العنوان</th>
                                    <th class="px-4 py-3 font-bold">الفرع</th>
                                    <th class="px-4 py-3 font-bold">الحالة</th>
                                    <th class="px-4 py-3 font-bold">آخر اتصال</th>
                                    <th class="px-4 py-3 font-bold">آخر سحب</th>
                                    <th class="px-4 py-3 font-bold">البصمات</th>
                                    <th class="px-4 py-3 font-bold">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant/30">
                                @foreach($companyDevices as $device)
                                    @php $deviceStatus = $device->connectionStatus(); @endphp
                                    <tr class="transition hover:bg-surface-container-high/40">
                                        <td class="px-4 py-3">
                                            <p class="font-bold text-on-surface">{{ $device->name_ar }}</p>
                                            <p class="text-xs text-on-surface-variant">{{ $device->model ?? 'ZKTeco' }} @if($device->serial_number) &middot; <span dir="ltr">{{ $device->serial_number }}</span> @endif</p>
                                        </td>
                                        <td class="px-4 py-3 font-bold" dir="ltr">{{ $device->host }}:{{ $device->port }}</td>
                                        <td class="px-4 py-3">{{ $device->branch?->name_ar ?? '-' }}</td>
                                        <td class="px-4 py-3">
                                            <span class="rounded-full px-3 py-1 text-xs font-bold
                                                @if($deviceStatus === 'online') bg-green-100 text-green-800
                                                @elseif($deviceStatus === 'error') bg-red-100 text-red-800
                                                @elseif($deviceStatus === 'stale') bg-yellow-100 text-yellow-800
                                                @elseif($deviceStatus === 'disabled') bg-surface-container text-on-surface-variant
                                                @else bg-blue-100 text-blue-800 @endif"
                                                @if($device->last_error) title="{{ $device->last_error }}" @endif>
                                                @switch($deviceStatus)
                                                    @case('online') متصل @break
                                                    @case('error') خطأ اتصال @break
                                                    @case('stale') لم يُرَ حديثاً @break
                                                    @case('disabled') موقوف @break
                                                    @default لم يُختبر بعد
                                                @endswitch
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-xs">{{ $device->last_seen_at?->diffForHumans() ?? '-' }}</td>
                                        <td class="px-4 py-3 text-xs">{{ $device->last_pulled_at?->diffForHumans() ?? '-' }}</td>
                                        <td class="px-4 py-3 font-bold">{{ number_format($device->punches_count) }}</td>
                                        <td class="px-4 py-3">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <form method="POST" action="{{ route('devices.test', $device) }}">
                                                    @csrf
                                                    <button class="rounded-lg border border-outline-variant px-3 py-1.5 text-xs font-bold text-on-surface hover:bg-surface-container" title="اختبار الاتصال">اختبار</button>
                                                </form>
                                                <form method="POST" action="{{ route('devices.pull', $device) }}">
                                                    @csrf
                                                    <button class="rounded-lg bg-primary px-3 py-1.5 text-xs font-bold text-on-primary hover:opacity-90" title="سحب البصمات الآن">سحب الآن</button>
                                                </form>
                                                <form method="POST" action="{{ route('devices.toggle', $device) }}" onsubmit="return confirm('{{ $device->is_active ? 'إيقاف الجهاز؟ لن يُسحب منه حضور حتى إعادة تفعيله.' : 'إعادة تفعيل الجهاز؟' }}');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="rounded-lg border px-3 py-1.5 text-xs font-bold {{ $device->is_active ? 'border-red-200 text-red-700 hover:bg-red-50' : 'border-green-300 text-green-700 hover:bg-green-50' }}">
                                                        {{ $device->is_active ? 'إيقاف' : 'تفعيل' }}
                                                    </button>
                                                </form>
                                            </div>
                                            <details class="mt-2">
                                                <summary class="cursor-pointer text-xs font-bold text-primary hover:underline">تعديل</summary>
                                                <form method="POST" action="{{ route('devices.update', $device) }}" class="mt-3 grid gap-3 rounded-xl bg-surface-container-low p-4 sm:grid-cols-2">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="company_id" value="{{ $device->company_id }}">
                                                    <input type="hidden" name="is_active" value="{{ $device->is_active ? 1 : 0 }}">
                                                    <div>
                                                        <label class="mb-1 block text-xs font-bold text-on-surface-variant">اسم الجهاز</label>
                                                        <input name="name_ar" value="{{ $device->name_ar }}" class="stitch-input w-full p-2 text-sm" required>
                                                    </div>
                                                    <div>
                                                        <label class="mb-1 block text-xs font-bold text-on-surface-variant">الفرع</label>
                                                        <select name="branch_id" class="stitch-input w-full p-2 text-sm">
                                                            <option value="">—</option>
                                                            @foreach($company->branches as $branch)
                                                                <option value="{{ $branch->id }}" @selected($device->branch_id === $branch->id)>{{ $branch->name_ar }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label class="mb-1 block text-xs font-bold text-on-surface-variant">العنوان (IP / DDNS)</label>
                                                        <input name="host" value="{{ $device->host }}" class="stitch-input w-full p-2 text-sm" dir="ltr" required>
                                                    </div>
                                                    <div class="grid grid-cols-2 gap-3">
                                                        <div>
                                                            <label class="mb-1 block text-xs font-bold text-on-surface-variant">المنفذ</label>
                                                            <input name="port" type="number" value="{{ $device->port }}" class="stitch-input w-full p-2 text-sm" dir="ltr" required>
                                                        </div>
                                                        <div>
                                                            <label class="mb-1 block text-xs font-bold text-on-surface-variant">Comm Key</label>
                                                            <input name="comm_key" type="number" value="{{ $device->comm_key }}" class="stitch-input w-full p-2 text-sm" dir="ltr">
                                                        </div>
                                                    </div>
                                                    <div class="sm:col-span-2">
                                                        <button class="stitch-btn-primary px-6 py-2 text-sm">حفظ التعديلات</button>
                                                    </div>
                                                </form>
                                            </details>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        @endforeach

        <section class="rounded-2xl border border-outline-variant/50 bg-surface-container-low p-6 text-sm text-on-surface-variant">
            <h3 class="mb-3 font-bold text-on-surface">ملاحظات الربط الشبكي</h3>
            <ul class="list-inside list-disc space-y-1">
                <li><span class="font-bold">شبكة محلية (LAN):</span> أدخل عنوان IP المحلي للجهاز مباشرة (مثال: <span dir="ltr">192.168.1.201</span>).</li>
                <li><span class="font-bold">VPN بين الفروع:</span> أدخل عنوان الجهاز داخل شبكة الفرع — يجب أن يكون نفق VPN يسمح بمرور UDP على المنفذ 4370.</li>
                <li><span class="font-bold">IP ثابت أو DDNS:</span> أدخل العنوان العام أو اسم DDNS، مع توجيه المنفذ (Port Forwarding) <span dir="ltr">UDP 4370</span> من الراوتر إلى الجهاز.</li>
                <li><span class="font-bold">ربط الموظفين:</span> ضع «رقم المستخدم في جهاز البصمة» في ملف كل موظف — به تُنسب البصمات للموظف الصحيح.</li>
                <li>السحب التلقائي يعمل كل 15 دقيقة عبر المجدول، ويمكن السحب اليدوي بزر «سحب الآن».</li>
                <li>سجلات الحضور المدخلة يدوياً من HR لا يستبدلها الجهاز أبداً.</li>
            </ul>
        </section>
    </div>

    <script>
        (function () {
            const companySelect = document.getElementById('device-company');
            const branchSelect = document.getElementById('device-branch');
            if (!companySelect || !branchSelect) return;

            function sync() {
                let selectedVisible = false;
                for (const option of branchSelect.options) {
                    if (!option.value) continue;
                    const visible = option.dataset.company === companySelect.value;
                    option.hidden = !visible;
                    if (visible && option.selected) selectedVisible = true;
                }
                if (!selectedVisible) branchSelect.value = '';
            }

            companySelect.addEventListener('change', sync);
            sync();
        })();
    </script>
@endsection
