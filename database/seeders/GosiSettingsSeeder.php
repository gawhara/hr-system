<?php

namespace Database\Seeders;

use App\Models\GosiSetting;
use Illuminate\Database\Seeder;

class GosiSettingsSeeder extends Seeder
{
    /**
     * PLACEHOLDER VALUES — NOT VERIFIED. These are historical reference
     * figures only. The operator must confirm current rates against official
     * GOSI/HRSD sources before production use (verified_at stays null until
     * then). Note: GOSI rates for new joiners changed under the 2024 social
     * insurance law amendments — verify which scheme applies per employee.
     */
    public function run(): void
    {
        $defaults = [
            [
                'key' => 'saudi_employee_rate',
                'value' => 0.0975,
                'label_ar' => 'نسبة اشتراك الموظف السعودي (معاش + ساند)',
                'label_en' => 'Saudi employee contribution (pension + SANED)',
                'description_ar' => 'قيمة مرجعية غير موثقة — يجب التحقق من المصدر الرسمي للتأمينات الاجتماعية قبل الاستخدام التشغيلي.',
            ],
            [
                'key' => 'saudi_employer_rate',
                'value' => 0.1175,
                'label_ar' => 'نسبة اشتراك صاحب العمل عن السعودي (معاش + ساند + أخطار مهنية)',
                'label_en' => 'Employer contribution for Saudi (pension + SANED + hazards)',
                'description_ar' => 'قيمة مرجعية غير موثقة — يجب التحقق من المصدر الرسمي قبل الاستخدام التشغيلي.',
            ],
            [
                'key' => 'non_saudi_employee_rate',
                'value' => 0,
                'label_ar' => 'نسبة اشتراك الموظف غير السعودي',
                'label_en' => 'Non-Saudi employee contribution',
                'description_ar' => 'غير السعودي لا يخصم منه اشتراك تأمينات افتراضياً (أخطار مهنية على صاحب العمل فقط) — تحقق من المصدر الرسمي.',
            ],
            [
                'key' => 'non_saudi_employer_rate',
                'value' => 0.02,
                'label_ar' => 'نسبة اشتراك صاحب العمل عن غير السعودي (أخطار مهنية)',
                'label_en' => 'Employer contribution for non-Saudi (occupational hazards)',
                'description_ar' => 'قيمة مرجعية غير موثقة — يجب التحقق من المصدر الرسمي قبل الاستخدام التشغيلي.',
            ],
            [
                'key' => 'max_gosi_wage',
                'value' => 45000,
                'label_ar' => 'الحد الأعلى للأجر الخاضع للتأمينات (شهرياً)',
                'label_en' => 'Maximum GOSI-eligible monthly wage',
                'description_ar' => 'قيمة مرجعية غير موثقة — يجب التحقق من المصدر الرسمي قبل الاستخدام التشغيلي.',
            ],
        ];

        foreach ($defaults as $setting) {
            GosiSetting::updateOrCreate(
                ['company_id' => null, 'key' => $setting['key']],
                $setting + ['verified_at' => null],
            );
        }
    }
}
