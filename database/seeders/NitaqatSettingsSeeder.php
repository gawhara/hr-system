<?php

namespace Database\Seeders;

use App\Models\EconomicActivity;
use App\Models\NitaqatSetting;
use Illuminate\Database\Seeder;

class NitaqatSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'key' => 'min_monthly_salary_for_full_weight',
                'value' => '4000',
                'label_ar' => 'الحد الأدنى للأجر الشهري لاحتساب السعودي بوزن كامل',
                'description_ar' => 'قيمة قابلة للتحديث بعد التحقق من قوى أو الدليل الرسمي.',
            ],
            [
                'key' => 'partial_salary_threshold',
                'value' => '3000',
                'label_ar' => 'حد الأجر الأدنى للاحتساب الجزئي',
                'description_ar' => 'من يقل راتبه عن هذا الحد لا يدخل في وزن التوطين الافتراضي.',
            ],
            [
                'key' => 'part_time_weight',
                'value' => '0.5',
                'label_ar' => 'وزن الدوام الجزئي',
                'description_ar' => 'وزن افتراضي للموظف السعودي بدوام جزئي.',
            ],
            [
                'key' => 'disability_weight_multiplier',
                'value' => '4',
                'label_ar' => 'معامل ذوي الإعاقة',
                'description_ar' => 'قيمة افتراضية يجب التحقق منها قبل الاعتماد التشغيلي.',
            ],
            [
                'key' => 'female_weight_bonus',
                'value' => '0',
                'label_ar' => 'وزن إضافي للمرأة العاملة',
                'description_ar' => 'اتركه صفر ما لم يكن النشاط مشمولا بوزن إضافي رسمي.',
            ],
            [
                'key' => 'higher_qualification_weight_bonus',
                'value' => '0',
                'label_ar' => 'وزن إضافي للمؤهلات العليا',
                'description_ar' => 'اتركه صفر حتى يتم التحقق من القاعدة الرسمية للنشاط.',
            ],
            [
                'key' => 'tenure_bonus_after_months',
                'value' => '0',
                'label_ar' => 'مدة الخدمة المطلوبة للوزن الإضافي',
                'description_ar' => 'صفر يعني أن وزن مدة الخدمة غير مفعل.',
            ],
            [
                'key' => 'tenure_weight_bonus',
                'value' => '0',
                'label_ar' => 'وزن إضافي حسب مدة الخدمة',
                'description_ar' => 'لا يفعل إلا بعد التحقق من الدليل الرسمي.',
            ],
        ];

        foreach ($settings as $setting) {
            NitaqatSetting::updateOrCreate(['key' => $setting['key']], $setting);
        }

        EconomicActivity::updateOrCreate(
            ['isic_code' => 'GENERIC'],
            [
                'name_ar' => 'نشاط عام - قالب قابل للتحديث',
                'min_establishment_size' => 5,
                'target_percentage_year1' => 0.25,
                'target_percentage_year2' => null,
                'target_percentage_year3' => null,
                'plan_effective_date' => '2026-04-26',
                'source_reference' => 'قالب داخلي - يجب استبداله بالنشاط الرسمي من قوى أو الدليل الإجرائي',
                'verified_at' => null,
                'is_active' => true,
            ]
        );
    }
}
