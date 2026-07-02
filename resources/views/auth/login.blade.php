<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ config('app.name') }} - تسجيل الدخول</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .login-input:focus-within {
            border-color: #0f4c81;
            box-shadow: 0 0 0 1px #0f4c81;
        }
    </style>
</head>
<body class="min-h-screen overflow-hidden bg-[#f9f9ff] text-[#111c2c]">
    <div class="flex min-h-screen items-stretch">
        <main class="relative z-10 flex w-full flex-col bg-white p-8 shadow-2xl md:p-16 lg:w-[450px] xl:w-[550px]">
            <div class="absolute left-8 top-8">
                <button type="button" class="flex items-center gap-2 text-sm font-bold text-[#00355f] transition-colors hover:text-[#00658d]">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <circle cx="12" cy="12" r="9"></circle>
                        <path d="M3 12h18M12 3c2.5 2.6 3.8 5.6 3.8 9S14.5 18.4 12 21M12 3C9.5 5.6 8.2 8.6 8.2 12S9.5 18.4 12 21"></path>
                    </svg>
                    <span>English</span>
                </button>
            </div>

            <div class="mx-auto flex w-full max-w-[400px] flex-1 flex-col justify-center">
                <div class="mb-12">
                    <div class="mb-8 flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#00355f] shadow-lg shadow-blue-950/20">
                            <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M4 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16"></path>
                                <path d="M16 8h2a2 2 0 0 1 2 2v11"></path>
                                <path d="M8 7h4M8 11h4M8 15h4M4 21h16"></path>
                            </svg>
                        </div>
                        <span class="text-lg font-bold tracking-tight text-[#00355f]">Horizon Enterprise</span>
                    </div>

                    <h1 class="mb-2 text-2xl font-bold text-[#111c2c]">تسجيل الدخول</h1>
                    <p class="text-base leading-7 text-[#42474f]">مرحباً بك في نظام إدارة الموارد البشرية</p>
                </div>

                @if($errors->any())
                    <div class="mb-5 rounded-lg border border-[#ffdad6] bg-[#ffdad6] px-4 py-3 text-sm font-bold text-[#93000a]">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf

                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-[#111c2c]" for="email">البريد الإلكتروني</label>
                        <div class="login-input relative flex items-center rounded-lg border border-[#c2c7d1] transition-all duration-200">
                            <svg class="absolute right-4 h-5 w-5 text-[#727780]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="M4 6h16v12H4z"></path>
                                <path d="m4 7 8 6 8-6"></path>
                            </svg>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                dir="ltr"
                                value="{{ old('email', 'admin@hr.local') }}"
                                autocomplete="username"
                                placeholder="name@company.com"
                                class="w-full rounded-lg border-none bg-transparent py-3 pl-4 pr-12 text-left text-base text-[#111c2c] outline-none focus:ring-0"
                                required
                                autofocus
                            >
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="block text-sm font-semibold text-[#111c2c]" for="password">كلمة المرور</label>
                            <button type="button" class="text-sm font-semibold text-[#00355f] transition-colors hover:text-[#00658d]">نسيت كلمة المرور؟</button>
                        </div>
                        <div class="login-input relative flex items-center rounded-lg border border-[#c2c7d1] transition-all duration-200">
                            <svg class="absolute right-4 h-5 w-5 text-[#727780]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <rect x="5" y="10" width="14" height="10" rx="2"></rect>
                                <path d="M8 10V7a4 4 0 0 1 8 0v3"></path>
                            </svg>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                value="password"
                                autocomplete="current-password"
                                placeholder="••••••••"
                                class="w-full rounded-lg border-none bg-transparent py-3 pl-12 pr-12 text-base text-[#111c2c] outline-none focus:ring-0"
                                required
                            >
                            <button id="togglePassword" class="absolute left-4 text-[#727780] transition-colors hover:text-[#00355f]" type="button" aria-label="إظهار كلمة المرور">
                                <svg id="eyeIcon" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <input class="h-4 w-4 rounded border-[#c2c7d1] text-[#00355f] focus:ring-[#00355f]" id="remember" name="remember" type="checkbox">
                        <label class="cursor-pointer text-sm text-[#42474f]" for="remember">تذكرني على هذا الجهاز</label>
                    </div>

                    <button class="flex w-full items-center justify-center gap-2 rounded-lg bg-[#00355f] py-4 text-base font-bold text-white shadow-lg shadow-blue-950/20 transition-all duration-200 hover:bg-[#0f4c81] active:scale-[0.99]" type="submit">
                        <span>دخول</span>
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M10 17 5 12l5-5"></path>
                            <path d="M5 12h14"></path>
                        </svg>
                    </button>
                </form>

                <div class="mt-12 border-t border-[#c2c7d1] pt-8 text-center">
                    <p class="text-sm text-[#42474f]">
                        تواجه مشكلة؟
                        <span class="font-bold text-[#00355f]">تواصل مع الدعم الفني</span>
                    </p>
                    <div class="mt-4 rounded-lg bg-[#f0f3ff] p-3 text-left text-sm font-semibold text-[#42474f]" dir="ltr">
                        <div>admin@hr.local</div>
                        <div>password</div>
                    </div>
                </div>
            </div>

            <div class="mt-auto text-center">
                <p class="text-sm text-[#727780]">© 2026 HR ERP. All rights reserved.</p>
            </div>
        </main>

        <section class="relative hidden flex-1 items-center justify-center overflow-hidden bg-[#00355f] lg:flex">
            <div class="absolute inset-0 z-0">
                <div class="h-full w-full bg-cover bg-center" style="background-image: url('{{ asset('images/login-office.jpg') }}')"></div>
                <div class="absolute inset-0 bg-gradient-to-br from-[#00355f]/90 via-[#00355f]/62 to-transparent"></div>
                <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 40px 40px;"></div>
            </div>

            <div class="relative z-10 max-w-2xl p-16 text-right">
                <div class="mb-8 inline-block rounded-full border border-white/10 bg-[#3dbeff]/20 px-4 py-2 backdrop-blur-md">
                    <span class="flex items-center gap-2 text-xs font-bold tracking-widest text-[#8ebdf9]">
                        <span class="h-2 w-2 animate-pulse rounded-full bg-[#3dbeff]"></span>
                        نظام متوافق مع رؤية 2030
                    </span>
                </div>

                <h2 class="mb-8 text-[48px] font-extrabold leading-tight text-white">
                    نقلة نوعية في <br>
                    <span class="text-[#3dbeff]">إدارة الموارد البشرية</span>
                </h2>

                <div class="grid grid-cols-1 gap-6">
                    @foreach([
                        ['إدارة متكاملة لـ 4 شركات', 'نظام موحد لإدارة الفروع والشركات التابعة لضمان التناغم المؤسسي.', 'M4 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16M16 8h2a2 2 0 0 1 2 2v11M8 7h4M8 11h4M8 15h4M4 21h16'],
                        ['متوافق مع نظام حماية الأجور', 'معالجة دقيقة للرواتب والالتزام بمتطلبات الموارد البشرية.', 'M20 6 9 17l-5-5M12 3l7 4v5c0 5-3 8-7 9-4-1-7-4-7-9V7l7-4Z'],
                        ['بيانات موحدة بين الوحدات', 'سجل موظف واحد يغذي الإجازات والحضور والرواتب دون تكرار إدخال البيانات.', 'M13 2 3 14h8l-1 8 11-13h-8l1-7Z'],
                    ] as [$title, $body, $path])
                        <div class="flex items-start gap-4 rounded-xl border border-white/10 bg-white/5 p-6 backdrop-blur-sm transition-all duration-300 hover:-translate-y-1 hover:bg-white/10">
                            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-lg bg-[#3dbeff]/20">
                                <svg class="h-6 w-6 text-[#3dbeff]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path d="{{ $path }}"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="mb-1 text-lg font-bold text-white">{{ $title }}</h3>
                                <p class="text-sm leading-6 text-[#8ebdf9]/85">{{ $body }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="absolute bottom-0 left-0 p-12 opacity-25">
                <svg class="h-28 w-28 text-white" viewBox="0 0 120 120" fill="none" stroke="currentColor" stroke-width="6" aria-hidden="true">
                    <circle cx="60" cy="60" r="48"></circle>
                    <path d="M34 72h16l10-24 12 34 10-18h18"></path>
                </svg>
            </div>
        </section>
    </div>

    <script>
        (function () {
            var button = document.getElementById('togglePassword');
            var input = document.getElementById('password');

            if (!button || !input) {
                return;
            }

            button.addEventListener('click', function () {
                var hidden = input.getAttribute('type') === 'password';
                input.setAttribute('type', hidden ? 'text' : 'password');
                button.setAttribute('aria-label', hidden ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور');
            });
        })();
    </script>
</body>
</html>
