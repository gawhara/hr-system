<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>قسيمة راتب — {{ $item->employee?->name_ar }} — {{ $cycle->year }}/{{ str_pad($cycle->month, 2, '0', STR_PAD_LEFT) }}</title>
    @vite(['resources/css/app.css'])
    <style>
        :root { --slip-primary: #5b21b6; --slip-line: #d4d4d8; --slip-muted: #71717a; }
        html, body { background: #fff; }
        body { font-family: 'Cairo', ui-sans-serif, system-ui, sans-serif; color: #18181b; margin: 0; }
        .slip { max-width: 210mm; margin: 0 auto; padding: 12mm 14mm; position: relative; }
        .slip-toolbar { display: flex; gap: .5rem; justify-content: flex-start; padding: 1rem 0; }
        .slip-toolbar button, .slip-toolbar a {
            font: inherit; font-weight: 700; font-size: .85rem; cursor: pointer; text-decoration: none;
            border-radius: .75rem; padding: .5rem 1.25rem; border: 1px solid var(--slip-line); color: #18181b; background: #fff;
        }
        .slip-toolbar button.primary { background: var(--slip-primary); border-color: var(--slip-primary); color: #fff; }
        .slip-head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid var(--slip-primary); padding-bottom: .75rem; }
        .slip-head h1 { font-size: 1.35rem; font-weight: 900; color: var(--slip-primary); margin: 0; }
        .slip-head .co { font-size: 1.05rem; font-weight: 800; margin: 0 0 .15rem; }
        .muted { color: var(--slip-muted); font-size: .75rem; }
        .badge { display: inline-block; border-radius: 999px; padding: .15rem .7rem; font-size: .7rem; font-weight: 700; }
        .badge-locked { background: #dcfce7; color: #166534; }
        .badge-open { background: #fef9c3; color: #854d0e; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: .35rem 2rem; margin: 1rem 0; }
        .kv { display: flex; justify-content: space-between; gap: 1rem; font-size: .85rem; padding: .3rem 0; border-bottom: 1px dashed var(--slip-line); }
        .kv .k { color: var(--slip-muted); font-weight: 600; white-space: nowrap; }
        .kv .v { font-weight: 800; text-align: end; }
        table.money { width: 100%; border-collapse: collapse; margin-top: .5rem; font-size: .85rem; }
        table.money caption { caption-side: top; text-align: start; font-weight: 900; font-size: .95rem; padding: .9rem 0 .4rem; color: var(--slip-primary); }
        table.money th, table.money td { border: 1px solid var(--slip-line); padding: .4rem .7rem; text-align: start; }
        table.money th { background: #f4f4f5; font-size: .75rem; color: var(--slip-muted); }
        table.money td.num { text-align: end; font-variant-numeric: tabular-nums; font-weight: 700; direction: ltr; }
        tr.total td { background: #f5f3ff; font-weight: 900; }
        .net { margin-top: 1.25rem; border: 2px solid var(--slip-primary); border-radius: .9rem; padding: .9rem 1.25rem; display: flex; justify-content: space-between; align-items: center; }
        .net .label { font-weight: 900; font-size: 1rem; }
        .net .amount { font-weight: 900; font-size: 1.5rem; color: var(--slip-primary); direction: ltr; }
        .watermark { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; pointer-events: none; }
        .watermark span { font-size: 5rem; font-weight: 900; color: rgba(220, 38, 38, .12); transform: rotate(-25deg); white-space: nowrap; }
        .slip-foot { margin-top: 1.5rem; border-top: 1px solid var(--slip-line); padding-top: .6rem; display: flex; justify-content: space-between; }
        @media print {
            .slip-toolbar { display: none; }
            .slip { padding: 0; max-width: none; }
            @page { size: A4; margin: 12mm; }
        }
    </style>
</head>
<body>
@php
    $employee = $item->employee;
    $maskedId = $employee?->national_id
        ? mb_substr($employee->national_id, 0, 2) . '******' . mb_substr($employee->national_id, -2)
        : '—';
    $maskedIban = $employee?->iban ? '…' . mb_substr($employee->iban, -4) : '—';
    $earnings = [
        ['الراتب الأساسي', 'Basic salary', $item->basic_salary],
        ['بدل السكن', 'Housing allowance', $item->housing_allowance],
        ['بدل النقل', 'Transportation allowance', $item->transportation_allowance],
        ['بدلات أخرى', 'Other allowances', $item->other_allowances],
        ['عمل إضافي', 'Overtime', $item->overtime],
        ['مستحقات سابقة', 'Previous dues', $item->previous_dues],
    ];
    $deductions = [
        ['خصم غياب', 'Absence', $item->absence_deduction],
        ['خصم تأخير', 'Delays', $item->delay_deduction],
        ['خصم إجازات', 'Unpaid leave', $item->leave_deduction],
        ['إنذارات وجزاءات', 'Warnings & penalties', $item->warnings_penalties],
        ['خصم تأمين', 'Insurance deduction', $item->insurance_deduction],
        ['أقساط قروض', 'Loan instalments', $item->loans],
        ['التأمينات الاجتماعية', 'GOSI (employee share)', $item->social_insurance_saudi],
    ];
    $payments = collect([
        ['نقداً', 'Cash', $item->cash],
        ['تحويل الراجحي', 'Al Rajhi transfer', $item->al_rajhi_transfer],
        ['تحويل بنك البلاد', 'Bank Albilad transfer', $item->bank_albilad_transfer],
        ['تحويل بنك الرياض', 'Riyad Bank transfer', $item->riyad_bank_transfer],
    ])->filter(fn ($row) => (float) $row[2] > 0);
@endphp

<div class="slip">
    @unless($cycle->isLocked())
        <div class="watermark"><span>غير نهائي — PRELIMINARY</span></div>
    @endunless

    <div class="slip-toolbar">
        <button class="primary" onclick="window.print()">طباعة / حفظ PDF</button>
        <a href="{{ url()->previous() }}">رجوع</a>
    </div>

    <div class="slip-head">
        <div>
            <p class="co">{{ $cycle->company?->name_ar }}</p>
            <p class="muted">{{ $cycle->company?->name_en }}@if($cycle->branch) — {{ $cycle->branch->name_ar }}@endif</p>
        </div>
        <div style="text-align: end;">
            <h1>قسيمة راتب <span class="muted" style="font-size:.8rem; font-weight:700;">Payslip</span></h1>
            <p class="muted" style="margin:.25rem 0 0;">
                الفترة {{ $cycle->year }}/{{ str_pad($cycle->month, 2, '0', STR_PAD_LEFT) }}
                ({{ $cycle->period_starts_on?->format('Y-m-d') }} — {{ $cycle->period_ends_on?->format('Y-m-d') }})
            </p>
            <p style="margin:.35rem 0 0;">
                <span class="badge {{ $cycle->isLocked() ? 'badge-locked' : 'badge-open' }}">
                    {{ ['draft' => 'مسودة', 'under_review' => 'قيد المراجعة', 'approved' => 'معتمد', 'locked' => 'مقفل'][$cycle->status] ?? $cycle->status }}
                </span>
                @if($cycle->parent_cycle_id)
                    <span class="badge badge-open">مسير تسوية #{{ $cycle->run_sequence }}</span>
                @endif
            </p>
        </div>
    </div>

    <div class="grid-2">
        <div class="kv"><span class="k">الموظف / Employee</span><span class="v">{{ $employee?->name_ar }}</span></div>
        <div class="kv"><span class="k">الرقم الوظيفي / Code</span><span class="v">{{ $employee?->employee_code ?? '—' }}</span></div>
        <div class="kv"><span class="k">الهوية / الإقامة / ID</span><span class="v" style="direction:ltr;">{{ $maskedId }}</span></div>
        <div class="kv"><span class="k">القسم / Department</span><span class="v">{{ $employee?->department?->name_ar ?? '—' }}</span></div>
        <div class="kv"><span class="k">المسمى الوظيفي / Position</span><span class="v">{{ $employee?->position?->name_ar ?? '—' }}</span></div>
        <div class="kv"><span class="k">البنك / Bank</span><span class="v">{{ $employee?->bank_name ?? '—' }} <span class="muted" style="direction:ltr;">{{ $maskedIban }}</span></span></div>
    </div>

    <table class="money">
        <caption>الاستحقاقات / Earnings</caption>
        <thead><tr><th>البند</th><th>Item</th><th style="width:22%; text-align:end;">المبلغ (ر.س) / SAR</th></tr></thead>
        <tbody>
            @foreach($earnings as [$ar, $en, $amount])
                @if((float) $amount > 0 || in_array($en, ['Basic salary', 'Housing allowance']))
                    <tr><td>{{ $ar }}</td><td class="muted">{{ $en }}</td><td class="num">{{ number_format((float) $amount, 2) }}</td></tr>
                @endif
            @endforeach
            <tr class="total"><td>إجمالي الاستحقاقات</td><td class="muted">Gross total</td><td class="num">{{ number_format((float) $item->gross_total, 2) }}</td></tr>
        </tbody>
    </table>

    <table class="money">
        <caption>الاستقطاعات / Deductions</caption>
        <thead><tr><th>البند</th><th>Item</th><th style="width:22%; text-align:end;">المبلغ (ر.س) / SAR</th></tr></thead>
        <tbody>
            @foreach($deductions as [$ar, $en, $amount])
                @if((float) $amount > 0 || $en === 'GOSI (employee share)')
                    <tr><td>{{ $ar }}</td><td class="muted">{{ $en }}</td><td class="num">{{ number_format((float) $amount, 2) }}</td></tr>
                @endif
            @endforeach
            <tr class="total"><td>إجمالي الاستقطاعات</td><td class="muted">Total deductions</td><td class="num">{{ number_format((float) $item->total_deductions, 2) }}</td></tr>
        </tbody>
    </table>

    <div class="net">
        <span class="label">صافي الراتب / Net salary</span>
        <span class="amount">SAR {{ number_format((float) $item->net_salary, 2) }}</span>
    </div>

    @if($payments->isNotEmpty())
        <table class="money">
            <caption>طريقة الصرف / Payment method</caption>
            <thead><tr><th>الطريقة</th><th>Method</th><th style="width:22%; text-align:end;">المبلغ (ر.س) / SAR</th></tr></thead>
            <tbody>
                @foreach($payments as [$ar, $en, $amount])
                    <tr><td>{{ $ar }}</td><td class="muted">{{ $en }}</td><td class="num">{{ number_format((float) $amount, 2) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="slip-foot muted">
        <span>وثيقة داخلية صادرة من نظام SMARS HR — للاستفسار راجع مسؤول الرواتب.</span>
        <span>أُنشئت في {{ now()->format('Y-m-d H:i') }}</span>
    </div>
</div>
</body>
</html>
