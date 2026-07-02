<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypesSeeder extends Seeder
{
    /**
     * Document set per AGENT.md; alert lead times are defaults the HR team
     * can adjust per type without a code change.
     */
    public function run(): void
    {
        $types = [
            ['key' => 'iqama', 'name_ar' => 'الإقامة', 'name_en' => 'Iqama', 'icon' => 'badge', 'alert_days' => [90, 60, 30]],
            ['key' => 'passport', 'name_ar' => 'جواز السفر', 'name_en' => 'Passport', 'icon' => 'travel_explore', 'alert_days' => [90, 30]],
            ['key' => 'contract', 'name_ar' => 'عقد العمل', 'name_en' => 'Employment Contract', 'icon' => 'contract', 'alert_days' => [60, 30]],
            ['key' => 'work_permit', 'name_ar' => 'رخصة العمل', 'name_en' => 'Work Permit', 'icon' => 'work', 'alert_days' => [60, 30]],
            ['key' => 'professional_license', 'name_ar' => 'رخصة / شهادة مهنية', 'name_en' => 'Professional License / Certification', 'icon' => 'workspace_premium', 'alert_days' => [60, 30]],
            ['key' => 'medical_insurance', 'name_ar' => 'التأمين الطبي', 'name_en' => 'Medical Insurance', 'icon' => 'medical_services', 'alert_days' => [30, 15]],
            ['key' => 'qualification', 'name_ar' => 'مؤهل علمي', 'name_en' => 'Qualification Certificate', 'icon' => 'school', 'requires_expiry' => false, 'alert_days' => null],
        ];

        foreach ($types as $type) {
            DocumentType::updateOrCreate(['key' => $type['key']], $type + ['requires_expiry' => true]);
        }
    }
}
