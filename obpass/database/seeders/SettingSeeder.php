<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General
            ['key' => 'company_name', 'value' => 'DICT Region II', 'group' => 'general', 'type' => 'string'],
            ['key' => 'company_address', 'value' => 'Tuguegarao City, Cagayan', 'group' => 'general', 'type' => 'string'],
            ['key' => 'company_logo', 'value' => null, 'group' => 'general', 'type' => 'string'],

            // Slip
            ['key' => 'slip_prefix', 'value' => 'OB', 'group' => 'slip', 'type' => 'string'],
            ['key' => 'slip_sequence_digits', 'value' => '4', 'group' => 'slip', 'type' => 'integer'],
            ['key' => 'working_hours_start', 'value' => '08:00', 'group' => 'slip', 'type' => 'string'],
            ['key' => 'working_hours_end', 'value' => '17:00', 'group' => 'slip', 'type' => 'string'],

            // Notification
            ['key' => 'notify_supervisor_on_submit', 'value' => 'true', 'group' => 'notification', 'type' => 'boolean'],
            ['key' => 'notify_employee_on_approve', 'value' => 'true', 'group' => 'notification', 'type' => 'boolean'],
            ['key' => 'notify_employee_on_return', 'value' => 'true', 'group' => 'notification', 'type' => 'boolean'],
            ['key' => 'notify_supervisor_on_depart', 'value' => 'true', 'group' => 'notification', 'type' => 'boolean'],
            ['key' => 'notify_supervisor_on_arrive', 'value' => 'true', 'group' => 'notification', 'type' => 'boolean'],
            ['key' => 'notify_hr_on_certificate', 'value' => 'true', 'group' => 'notification', 'type' => 'boolean'],
            ['key' => 'notify_employee_on_verify', 'value' => 'true', 'group' => 'notification', 'type' => 'boolean'],
            ['key' => 'notify_employee_on_complete', 'value' => 'true', 'group' => 'notification', 'type' => 'boolean'],

            // PDF
            ['key' => 'pdf_company_tagline', 'value' => 'Official Business Pass Slip', 'group' => 'pdf', 'type' => 'string'],
            ['key' => 'pdf_primary_color', 'value' => '#1e3a5f', 'group' => 'pdf', 'type' => 'string'],
            ['key' => 'pdf_show_qr', 'value' => 'true', 'group' => 'pdf', 'type' => 'boolean'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
