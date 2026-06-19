<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General settings
            [
                'key' => 'app_name',
                'value' => 'DICT Region II Official Business Pass Slip System',
                'group' => 'general',
                'type' => 'string',
            ],
            [
                'key' => 'organization_name',
                'value' => 'Department of Information and Communications Technology - Region II',
                'group' => 'general',
                'type' => 'string',
            ],
            [
                'key' => 'default_departure_grace_period',
                'value' => '30',
                'group' => 'general',
                'type' => 'integer',
            ],

            // Workflow settings
            [
                'key' => 'require_supervisor_approval',
                'value' => 'true',
                'group' => 'workflow',
                'type' => 'boolean',
            ],
            [
                'key' => 'require_vehicle_approval',
                'value' => 'false',
                'group' => 'workflow',
                'type' => 'boolean',
            ],
            [
                'key' => 'allow_returned_slips',
                'value' => 'true',
                'group' => 'workflow',
                'type' => 'boolean',
            ],
            [
                'key' => 'slip_number_format',
                'value' => 'OB-{YYYY}-{sequence}',
                'group' => 'workflow',
                'type' => 'string',
            ],
            [
                'key' => 'emergency_slip_auto_approve',
                'value' => 'false',
                'group' => 'workflow',
                'type' => 'boolean',
            ],

            // Notification settings
            [
                'key' => 'enable_email_notifications',
                'value' => 'true',
                'group' => 'notification',
                'type' => 'boolean',
            ],
            [
                'key' => 'enable_push_notifications',
                'value' => 'true',
                'group' => 'notification',
                'type' => 'boolean',
            ],
            [
                'key' => 'notification_from_email',
                'value' => 'noreply@dictr2.cloud',
                'group' => 'notification',
                'type' => 'string',
            ],

            // PDF settings
            [
                'key' => 'pdf_generate_duplicate',
                'value' => 'true',
                'group' => 'pdf',
                'type' => 'boolean',
            ],
            [
                'key' => 'pdf_header_text',
                'value' => 'Official Business Pass Slip',
                'group' => 'pdf',
                'type' => 'string',
            ],
            [
                'key' => 'pdf_footer_text',
                'value' => 'DICT Region II - Cagayan Valley',
                'group' => 'pdf',
                'type' => 'string',
            ],
            [
                'key' => 'pdf_include_qr_code',
                'value' => 'true',
                'group' => 'pdf',
                'type' => 'boolean',
            ],
            [
                'key' => 'pdf_storage_path',
                'value' => 'pdfs/pass_slips',
                'group' => 'pdf',
                'type' => 'string',
            ],

            // QR settings
            [
                'key' => 'qr_code_size',
                'value' => '300',
                'group' => 'general',
                'type' => 'integer',
            ],
            [
                'key' => 'qr_code_error_correction',
                'value' => 'H',
                'group' => 'general',
                'type' => 'string',
            ],

            // Guard kiosk settings
            [
                'key' => 'kiosk_auto_logout_minutes',
                'value' => '5',
                'group' => 'general',
                'type' => 'integer',
            ],
            [
                'key' => 'kiosk_fullscreen_mode',
                'value' => 'true',
                'group' => 'general',
                'type' => 'boolean',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'group' => $setting['group'],
                    'type' => $setting['type'],
                ]
            );
        }

        $this->command->info('Settings seeded successfully.');
    }
}