<?php

namespace Database\Seeders;

use App\Models\EmailFormat;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmailFormatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        EmailFormat::truncate();
        DB::table('email_formats')->insert([
            [
                'type' => config('constants.email_format.type.header'),
                'label' => 'Header',
                'body' => '<!DOCTYPE html><html><head></head><body><p><img src="' . url('assets/media/logos/eastern_techno_solutions_logo.png') . '" alt="Logo" width="200px" height="80px" /></p></body></html>',
                'created_at' => config('constants.calender.date_time'),
                'updated_at' => config('constants.calender.date_time'),
            ],
            [
                'type' => config('constants.email_format.type.footer'),
                'label' => 'Footer',
                'body' => '<p style="text-align: center;">Copyright &copy; 2024 UAT The Tech Rewards. All rights reserved.</p>',
                'created_at' => config('constants.calender.date_time'),
                'updated_at' => config('constants.calender.date_time'),
            ],
            [
                'type' => config('constants.email_format.type.signature'),
                'label' => 'Signature',
                'body' => '<p> - Team Laravel</p>',
                'created_at' => config('constants.calender.date_time'),
                'updated_at' => config('constants.calender.date_time'),
            ],
        ]);
    }
}
