@extends('layouts.app')

@section('title', 'أجهزة البصمة')

@section('content')
    <div class="space-y-8">

        {{-- ===================== Header ===================== --}}
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
            <div>
                <p class="font-label text-xs font-bold uppercase tracking-[0.25em] text-tertiary">Biometric Fleet Console</p>
                <h2 class="mt-2 font-headline text-display-lg font-bold text-primary">أجهزة البصمة</h2>
                <p class="mt-2 max-w-3xl text-on-surface-variant">
                    وحدة تشغيل مركزية لأجهزة الحضور في شركات المجموعة الأربع. البروتوكول: ZKTeco عبر
                    <span dir="ltr" class="font-bold">UDP 4370</span> — يعمل عبر الشبكة المحلية أو VPN أو IP ثابت أو DDNS.
                    السحب التلقائي يعمل كل 15 دقيقة؛ السجلات اليدوية من HR لا تُستبدل أبداً.
                </p>
            </div>
            <form method="POST" action="{{ route('devices.pull-all') }}"
                  onsubmit="return confirm('سحب البصمات من جميع الأجهزة المفعلة الآن؟\nقد يستغرق حتى 8 ثوانٍ لكل جهاز غير متصل.');">
                @csrf
                <button class="stitch-btn-primary flex items-center gap-2 px-6 py-3">
                    <span class="material-symbols-outlined">cloud_sync</span>
                    <span>سحب من جميع الأجهزة</span>
                </button>
            </form>
        </div>

        {{-- ===================== Fleet stats ===================== --}}
        <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6">
            @foreach([
                ['الأجهزة', $fleet['total'], 'devices_other', 'text-primary'],
                ['مفعلة', $fleet['active'], 'power', 'text-primary'],
                ['متصلة الآن', $fleet['online'], 'wifi', 'text-green-700'],
                ['أخطاء اتصال', $fleet['errors'], 'wifi_off', $fleet['errors'] > 0 ? 'text-red-700' : 'text-primary'],
                ['إجمالي البصمات', number_format($fleet['punches']), 'fingerprint', 'text-primary'],
                ['بصمات غير مطابقة', number_format($fleet['unmatched']), 'person_alert', $fleet['unmatched'] > 0 ? 'text-yellow-700' : 'text-primary'],
            ] as [$label, $value, $icon, $color])
                <div class="rounded-2xl border border-outline-variant/50 bg-white p-4 shadow-sm">
                    <div class="flex items-center gap-2 text-on-surface-variant">
                        <span class="material-symbols-outlined text-[20px]">{{ $icon }}</span>
                        <span class="text-xs font-bold">{{ $label }}</span>
                    </div>
                    <p class="mt-2 font-headline text-2xl font-black {{ $color }}">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        @if(session('status'))
            <div class="rounded-2xl border border-green-300 bg-green-50 p-4 text-sm font-bold text-green-800">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="rounded-2xl border border-red-300 bg-red-50 p-4 text-sm font-bold text-red-800">{{ $errors->first() }}</div>
        @endif

        {{-- ===================== Add device ===================== --}}
        <details class="glass-card rounded-2xl p-6" {{ $errors->any() ? 'open' : '' }}>
            <summary class="flex cursor-pointer list-none items-center gap-3 font-headline text-xl font-bold text-primary">
                <span class="material-symbols-outlined">add_circle</span>
                <span>إضافة جهاز جديد</span>
            </summary>

            <div class="mt-6 space-y-5">
                {{-- Connection method selector --}}
                <div>
                    <label class="mb-2 block text-sm font-bold text-on-surface-variant">طريقة الوصول للجهاز</label>
                    <div class="flex flex-wrap gap-2" id="conn-methods">
                        @foreach([
                            'lan' => ['شبكة محلية LAN', 'lan'],
                            'vpn' => ['VPN بين الفروع', 'vpn_lock'],
                            'static' => ['IP ثابت', 'public'],
                            'ddns' => ['DDNS', 'dns'],
                        ] as $method => [$label, $icon])
                            <button type="button" data-method="{{ $method }}"
                                class="conn-method flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-bold transition
                                       {{ $loop->first ? 'border-primary bg-primary-fixed text-primary' : 'border-outline-variant/60 bg-white text-on-surface-variant hover:border-primary/40' }}">
                                <span class="material-symbols-outlined text-[20px]">{{ $icon }}</span>
                                <span>{{ $label }}</span>
                            </button>
                        @endforeach
                    </div>
                    <p id="conn-hint" class="mt-2 rounded-xl bg-surface-container-low p-3 text-xs leading-relaxed text-on-surface-variant"></p>
                </div>

                <form method="POST" action="{{ route('devices.store') }}" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
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
                        <input name="name_ar" value="{{ old('name_ar') }}" class="stitch-input w-full p-3" placeholder="بصمة البوابة الرئيسية" required>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-bold text-on-surface-variant">العنوان *</label>
                        <input name="host" id="host-input" value="{{ old('host') }}" class="stitch-input w-full p-3" dir="ltr" placeholder="192.168.1.201" required>
                        <p class="mt-1 text-[11px] text-on-surface-variant">IP أو اسم مضيف قابل للوصول من هذا الخادم.</p>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-bold text-on-surface-variant">المنفذ *</label>
                        <input name="port" type="number" value="{{ old('port', 4370) }}" class="stitch-input w-full p-3" dir="ltr" min="1" max="65535" required>
                        <p class="mt-1 text-[11px] text-on-surface-variant">الافتراضي لأجهزة ZKTeco: <span dir="ltr">4370</span>.</p>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-bold text-on-surface-variant">مفتاح الاتصال (Comm Key)</label>
                        <input name="comm_key" type="number" value="{{ old('comm_key', 0) }}" class="stitch-input w-full p-3" dir="ltr" min="0">
                        <p class="mt-1 text-[11px] text-on-surface-variant">من قائمة الجهاز: الاتصال ← الأمان. اتركه 0 إن لم يُضبط.</p>
                    </div>
                    <div class="sm:col-span-2 xl:col-span-3 flex flex-wrap items-center gap-3">
                        <button class="stitch-btn-primary px-8 py-3">حفظ الجهاز</button>
                        <p class="text-xs text-on-surface-variant">بعد الحفظ استخدم «اختبار» — يُعبأ الطراز والرقم التسلسلي تلقائياً ويُفحص انحراف ساعة الجهاز.</p>
                    </div>
                </form>
            </div>
        </details>

        {{-- ===================== Devices per company ===================== --}}
        @foreach($companies as $company)
            @php $companyDevices = $devicesByCompany[$company->id] ?? collect(); @endphp
            <section class="glass-card rounded-2xl p-6">
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="font-headline text-xl font-bold text-on-surface">{{ $company->name_ar }}</h3>
                    <span class="rounded-full bg-surface-container px-3 py-1 text-xs font-bold text-on-surface-variant">{{ $companyDevices->count() }} جهاز</span>
                </div>

                @if($companyDevices->isEmpty())
                    <p class="rounded-xl bg-surface-container-low p-4 text-sm text-on-surface-variant">لا توجد أجهزة مسجلة لهذه الشركة بعد — أضف أول جهاز من النموذج أعلاه.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-right text-sm">
                            <thead class="bg-surface-container-low text-on-surface-variant">
                                <tr>
                                    <th class="px-4 py-3 font-bold">الجهاز</th>
                                    <th class="px-4 py-3 font-bold">الاتصال</th>
                                    <th class="px-4 py-3 font-bold">الفرع</th>
                                    <th class="px-4 py-3 font-bold">الحالة</th>
                                    <th class="px-4 py-3 font-bold">آخر اتصال / سحب</th>
                                    <th class="px-4 py-3 font-bold">البصمات</th>
                                    <th class="px-4 py-3 font-bold">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant/30 align-top">
                                @foreach($companyDevices as $device)
                                    @php $deviceStatus = $device->connectionStatus(); @endphp
                                    <tr class="transition hover:bg-surface-container-high/40">
                                        <td class="px-4 py-3">
                                            <p class="font-bold text-on-surface">{{ $device->name_ar }}</p>
                                            <p class="mt-0.5 text-xs text-on-surface-variant">
                                                {{ $device->model ?? 'ZKTeco' }}
                                                @if($device->serial_number) &middot; <span dir="ltr">{{ $device->serial_number }}</span> @endif
                                            </p>
                                        </td>
                                        <td class="px-4 py-3">
                                            <p class="font-bold" dir="ltr">{{ $device->host }}:{{ $device->port }}</p>
                                            <p class="mt-0.5 text-[11px] text-on-surface-variant" dir="ltr">UDP · Comm Key {{ $device->comm_key > 0 ? '●●●' : '0' }}</p>
                                        </td>
                                        <td class="px-4 py-3">{{ $device->branch?->name_ar ?? '-' }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold
                                                @if($deviceStatus === 'online') bg-green-100 text-green-800
                                                @elseif($deviceStatus === 'error') bg-red-100 text-red-800
                                                @elseif($deviceStatus === 'stale') bg-yellow-100 text-yellow-800
                                                @elseif($deviceStatus === 'disabled') bg-surface-container text-on-surface-variant
                                                @else bg-blue-100 text-blue-800 @endif">
                                                <span class="h-1.5 w-1.5 rounded-full
                                                    @if($deviceStatus === 'online') bg-green-600
                                                    @elseif($deviceStatus === 'error') bg-red-600
                                                    @elseif($deviceStatus === 'stale') bg-yellow-600
                                                    @else bg-current opacity-40 @endif"></span>
                                                @switch($deviceStatus)
                                                    @case('online') متصل @break
                                                    @case('error') خطأ اتصال @break
                                                    @case('stale') لم يُرَ حديثاً @break
                                                    @case('disabled') موقوف @break
                                                    @default لم يُختبر بعد
                                                @endswitch
                                            </span>
                                            @if($device->last_error)
                                                <p class="mt-1 max-w-[220px] text-[11px] leading-snug text-red-700">{{ \Illuminate\Support\Str::limit($device->last_error, 120) }}</p>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-xs leading-relaxed">
                                            <span class="text-on-surface-variant">اتصال:</span> {{ $device->last_seen_at?->diffForHumans() ?? '—' }}<br>
                                            <span class="text-on-surface-variant">سحب:</span> {{ $device->last_pulled_at?->diffForHumans() ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 font-bold">{{ number_format($device->punches_count) }}</td>
                                        <td class="px-4 py-3">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <form method="POST" action="{{ route('devices.test', $device) }}">
                                                    @csrf
                                                    <button class="rounded-lg border border-outline-variant px-3 py-1.5 text-xs font-bold text-on-surface hover:bg-surface-container" title="فحص الاتصال والهوية وساعة الجهاز">اختبار</button>
                                                </form>
                                                <form method="POST" action="{{ route('devices.pull', $device) }}">
                                                    @csrf
                                                    <button class="rounded-lg bg-primary px-3 py-1.5 text-xs font-bold text-on-primary hover:opacity-90">سحب الآن</button>
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

        {{-- ===================== Unmatched punches ===================== --}}
        @if($unmatched->isNotEmpty())
            <section class="rounded-2xl border border-yellow-300 bg-yellow-50/70 p-6">
                <div class="mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-yellow-700">person_alert</span>
                    <h3 class="font-headline text-xl font-bold text-yellow-900">بصمات بدون موظف مطابق</h3>
                </div>
                <p class="mb-4 text-sm text-yellow-900">
                    هذه أرقام مستخدمين موجودة على الأجهزة ولا يقابلها موظف. الحل: افتح ملف الموظف الصحيح
                    وضع الرقم في حقل «رقم المستخدم في جهاز البصمة»، ثم اضغط «سحب الآن» — تُنسب البصمات المخزنة تلقائياً.
                </p>
                <div class="overflow-x-auto rounded-xl bg-white">
                    <table class="w-full text-right text-sm">
                        <thead class="bg-surface-container-low text-on-surface-variant">
                            <tr>
                                <th class="px-4 py-2 font-bold">رقم المستخدم بالجهاز</th>
                                <th class="px-4 py-2 font-bold">الجهاز</th>
                                <th class="px-4 py-2 font-bold">الشركة</th>
                                <th class="px-4 py-2 font-bold">عدد البصمات</th>
                                <th class="px-4 py-2 font-bold">آخر بصمة</th>
                                <th class="px-4 py-2 font-bold"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/30">
                            @foreach($unmatched as $row)
                                <tr>
                                    <td class="px-4 py-2 font-black text-primary" dir="ltr">{{ $row->device_user_id }}</td>
                                    <td class="px-4 py-2">{{ $row->device?->name_ar ?? '—' }}</td>
                                    <td class="px-4 py-2">{{ $row->device?->company?->name_ar ?? '—' }}</td>
                                    <td class="px-4 py-2 font-bold">{{ number_format($row->punch_count) }}</td>
                                    <td class="px-4 py-2 text-xs">{{ \Illuminate\Support\Carbon::parse($row->last_punch_at)->format('Y-m-d H:i') }}</td>
                                    <td class="px-4 py-2">
                                        <a href="{{ route('employees.index', ['company_id' => $row->device?->company_id]) }}" class="text-xs font-bold text-primary hover:underline">فتح دليل الموظفين ←</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        {{-- ===================== Technical guide ===================== --}}
        <section class="glass-card rounded-2xl p-6">
            <h3 class="mb-4 font-headline text-xl font-bold text-on-surface">دليل الربط الفني — ZKTeco</h3>
            <div class="space-y-3">

                <details class="rounded-xl border border-outline-variant/50 bg-surface-container-lowest p-4">
                    <summary class="cursor-pointer font-bold text-primary">1) شبكة محلية LAN — نفس شبكة الخادم</summary>
                    <ol class="mt-3 list-inside list-decimal space-y-1.5 text-sm text-on-surface-variant">
                        <li>على الجهاز: القائمة ← <span class="font-bold">الاتصال / COMM</span> ← Ethernet: عيّن IP ثابتاً داخل نطاق الشبكة (مثال <span dir="ltr" class="font-bold">192.168.1.201</span>)، وقناع الشبكة <span dir="ltr">255.255.255.0</span>، والبوابة عنوان الراوتر.</li>
                        <li>احجز نفس العنوان في الراوتر (DHCP Reservation) حتى لا يتغير بعد انقطاع الكهرباء.</li>
                        <li>من هذا الخادم تحقق من الوصول: <code dir="ltr" class="rounded bg-surface-container px-1">ping 192.168.1.201</code>.</li>
                        <li>أضف الجهاز أعلاه بالعنوان المحلي والمنفذ 4370 ثم «اختبار».</li>
                    </ol>
                </details>

                <details class="rounded-xl border border-outline-variant/50 bg-surface-container-lowest p-4">
                    <summary class="cursor-pointer font-bold text-primary">2) VPN بين الفروع — الطريقة الموصى بها للفروع البعيدة</summary>
                    <ol class="mt-3 list-inside list-decimal space-y-1.5 text-sm text-on-surface-variant">
                        <li>أنشئ نفق Site-to-Site VPN (WireGuard / OpenVPN / IPsec على راوترات مثل MikroTik أو FortiGate) بين شبكة المقر وشبكة الفرع.</li>
                        <li>تأكد أن النفق يمرر <span class="font-bold">UDP</span> وأن Route شبكة الفرع (مثال <span dir="ltr">192.168.20.0/24</span>) معلن للمقر.</li>
                        <li>ثبّت IP الجهاز داخل شبكة الفرع كما في خطوات LAN.</li>
                        <li>أضف الجهاز هنا بعنوانه داخل شبكة الفرع — لا حاجة لأي Port Forwarding، وهذه أكثر طريقة أماناً.</li>
                    </ol>
                </details>

                <details class="rounded-xl border border-outline-variant/50 bg-surface-container-lowest p-4">
                    <summary class="cursor-pointer font-bold text-primary">3) IP ثابت عام — عندما يملك الفرع IP إنترنت ثابتاً</summary>
                    <ol class="mt-3 list-inside list-decimal space-y-1.5 text-sm text-on-surface-variant">
                        <li>على راوتر الفرع: أنشئ قاعدة Port Forwarding: <span dir="ltr" class="font-bold">UDP 4370</span> ← IP الجهاز الداخلي.</li>
                        <li>قيّد القاعدة بمصدر واحد فقط = IP خادم المقر (Source IP Filtering) حتى لا يكون الجهاز مكشوفاً للإنترنت.</li>
                        <li>عيّن Comm Key غير صفري على الجهاز (القائمة ← الاتصال ← الأمان) — إلزامي عند أي تعريض للإنترنت.</li>
                        <li>أضف الجهاز هنا بالـ IP العام للفرع والمنفذ 4370 (أو منفذ خارجي مختلف إن استخدمت ترجمة منافذ).</li>
                    </ol>
                </details>

                <details class="rounded-xl border border-outline-variant/50 bg-surface-container-lowest p-4">
                    <summary class="cursor-pointer font-bold text-primary">4) DDNS — عندما يتغير IP الفرع باستمرار</summary>
                    <ol class="mt-3 list-inside list-decimal space-y-1.5 text-sm text-on-surface-variant">
                        <li>فعّل DDNS على راوتر الفرع (No-IP أو DynDNS أو خدمة الراوتر نفسه) واحصل على اسم مثل <span dir="ltr" class="font-bold">branch1-amniat.ddns.net</span>.</li>
                        <li>نفّذ نفس خطوات Port Forwarding وتقييد المصدر وComm Key المذكورة في طريقة IP الثابت.</li>
                        <li>أضف الجهاز هنا باسم DDNS بدلاً من الرقم — النظام يحلّ الاسم عند كل سحب فلا يتأثر بتغيّر العنوان.</li>
                    </ol>
                </details>

                <details class="rounded-xl border border-outline-variant/50 bg-surface-container-lowest p-4">
                    <summary class="cursor-pointer font-bold text-primary">ربط الموظفين واستكشاف الأخطاء</summary>
                    <div class="mt-3 space-y-4 text-sm text-on-surface-variant">
                        <div>
                            <p class="font-bold text-on-surface">ربط الموظفين:</p>
                            <p class="mt-1">رقم المستخدم المسجل على الجهاز (User ID عند التسجيل بالبصمة) يجب وضعه في ملف الموظف بحقل «رقم المستخدم في جهاز البصمة». استخدم نفس الرقم على جميع أجهزة الشركة الواحدة. البصمات غير المطابقة تظهر في اللوحة الصفراء أعلاه.</p>
                        </div>
                        <table class="w-full text-right text-xs">
                            <thead class="bg-surface-container-low">
                                <tr>
                                    <th class="px-3 py-2 font-bold">العرض</th>
                                    <th class="px-3 py-2 font-bold">السبب الأرجح</th>
                                    <th class="px-3 py-2 font-bold">الحل</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant/30">
                                <tr>
                                    <td class="px-3 py-2">«تعذر الاتصال» فوراً أو بعد مهلة</td>
                                    <td class="px-3 py-2">المنفذ غير موجه، جدار ناري يحجب UDP 4370، أو IP خاطئ</td>
                                    <td class="px-3 py-2">تحقق من ping، وقاعدة Port Forwarding/جدار الحماية، وأن الجهاز يعمل</td>
                                </tr>
                                <tr>
                                    <td class="px-3 py-2">اتصال ناجح لكن «تحقق من مفتاح الاتصال»</td>
                                    <td class="px-3 py-2">Comm Key على الجهاز يختلف عن المسجل هنا</td>
                                    <td class="px-3 py-2">طابق القيمة من قائمة الجهاز ← الاتصال ← الأمان</td>
                                </tr>
                                <tr>
                                    <td class="px-3 py-2">سحب ناجح لكن 0 سجلات جديدة دائماً</td>
                                    <td class="px-3 py-2">لا بصمات جديدة منذ آخر سحب (السحب لا يكرر المخزن)</td>
                                    <td class="px-3 py-2">طبيعي — جرّب بصمة اختبارية ثم «سحب الآن»</td>
                                </tr>
                                <tr>
                                    <td class="px-3 py-2">بصمات كثيرة «غير مطابقة»</td>
                                    <td class="px-3 py-2">حقل رقم البصمة فارغ أو مختلف في ملفات الموظفين</td>
                                    <td class="px-3 py-2">عبّئ الأرقام من اللوحة الصفراء أعلاه ثم اسحب مجدداً</td>
                                </tr>
                                <tr>
                                    <td class="px-3 py-2">أوقات حضور غير منطقية</td>
                                    <td class="px-3 py-2">ساعة الجهاز منحرفة</td>
                                    <td class="px-3 py-2">زر «اختبار» يعرض انحراف الساعة — اضبط وقت الجهاز من قائمته</td>
                                </tr>
                            </tbody>
                        </table>
                        <p class="rounded-xl bg-surface-container-low p-3 text-xs">
                            <span class="font-bold">ملاحظة أمنية:</span> بروتوكول ZKTeco القياسي غير مشفّر — فضّل دائماً VPN على تعريض المنفذ للإنترنت، وعند الاضطرار للتعريض قيّد المصدر بعنوان الخادم وفعّل Comm Key.
                        </p>
                    </div>
                </details>
            </div>
        </section>
    </div>

    <script>
        (function () {
            // Company → branch cascade
            const companySelect = document.getElementById('device-company');
            const branchSelect = document.getElementById('device-branch');

            function syncBranches() {
                if (!companySelect || !branchSelect) return;
                let selectedVisible = false;
                for (const option of branchSelect.options) {
                    if (!option.value) continue;
                    const visible = option.dataset.company === companySelect.value;
                    option.hidden = !visible;
                    if (visible && option.selected) selectedVisible = true;
                }
                if (!selectedVisible) branchSelect.value = '';
            }

            if (companySelect) {
                companySelect.addEventListener('change', syncBranches);
                syncBranches();
            }

            // Connection-method selector → contextual hint + placeholder
            const hints = {
                lan: {
                    hint: 'الجهاز على نفس شبكة الخادم: أدخل الـ IP المحلي مباشرة. احجز العنوان في الراوتر (DHCP Reservation) حتى لا يتغير.',
                    placeholder: '192.168.1.201',
                },
                vpn: {
                    hint: 'الجهاز في فرع موصول بنفق VPN: أدخل عنوانه داخل شبكة الفرع. تأكد أن النفق يمرر UDP وأن مسار شبكة الفرع معلن للخادم. الطريقة الأكثر أماناً.',
                    placeholder: '192.168.20.201',
                },
                static: {
                    hint: 'فرع بعنوان إنترنت ثابت: أدخل الـ IP العام. يتطلب Port Forwarding UDP 4370 على راوتر الفرع مع تقييد المصدر بعنوان هذا الخادم وComm Key غير صفري.',
                    placeholder: '82.167.44.10',
                },
                ddns: {
                    hint: 'فرع بعنوان متغير: أدخل اسم DDNS — يُحل الاسم عند كل سحب تلقائياً. نفس متطلبات Port Forwarding وComm Key.',
                    placeholder: 'branch1-amniat.ddns.net',
                },
            };

            const hintBox = document.getElementById('conn-hint');
            const hostInput = document.getElementById('host-input');
            const buttons = document.querySelectorAll('.conn-method');

            function selectMethod(method) {
                buttons.forEach((btn) => {
                    const active = btn.dataset.method === method;
                    btn.classList.toggle('border-primary', active);
                    btn.classList.toggle('bg-primary-fixed', active);
                    btn.classList.toggle('text-primary', active);
                    btn.classList.toggle('border-outline-variant/60', !active);
                    btn.classList.toggle('bg-white', !active);
                    btn.classList.toggle('text-on-surface-variant', !active);
                });
                if (hintBox) hintBox.textContent = hints[method].hint;
                if (hostInput) hostInput.placeholder = hints[method].placeholder;
            }

            buttons.forEach((btn) => btn.addEventListener('click', () => selectMethod(btn.dataset.method)));
            selectMethod('lan');
        })();
    </script>
@endsection
