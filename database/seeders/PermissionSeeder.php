<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::truncate();
        Cache::forget('getAllPermissions');

        Permission::insert([
            ['name' => 'emailformats', 'guard_name' => 'root', 'label' => 'Email Formats', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'view-emailformats', 'guard_name' => 'emailformats', 'label' => 'View', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'edit-emailformats', 'guard_name' => 'emailformats', 'label' => 'Edit', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'emailtemplates', 'guard_name' => 'root', 'label' => 'Email Templates', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'view-emailtemplates', 'guard_name' => 'emailtemplates', 'label' => 'View', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'show-emailtemplates', 'guard_name' => 'emailtemplates', 'label' => 'Show', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'edit-emailtemplates', 'guard_name' => 'emailtemplates', 'label' => 'Edit', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],

            ['name' => 'roles', 'label' => 'Role', 'guard_name' => 'root', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'view-role', 'label' => 'View', 'guard_name' => 'roles', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'show-role', 'label' => 'Show', 'guard_name' => 'roles', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'add-role', 'label' => 'Add', 'guard_name' => 'roles', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'edit-role', 'label' => 'Edit', 'guard_name' => 'roles', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'delete-role', 'label' => 'Delete', 'guard_name' => 'roles', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'bulkDelete-role', 'label' => 'Bulk Delete', 'guard_name' => 'roles', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'import-role', 'label' => 'Import', 'guard_name' => 'roles', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'export-role', 'label' => 'Export', 'guard_name' => 'roles', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],

            ['name' => 'users', 'label' => 'User', 'guard_name' => 'root', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'view-user', 'label' => 'View', 'guard_name' => 'users', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'show-user', 'label' => 'Show', 'guard_name' => 'users', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'add-user', 'label' => 'Add', 'guard_name' => 'users', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'edit-user', 'label' => 'Edit', 'guard_name' => 'users', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'delete-user', 'label' => 'Delete', 'guard_name' => 'users', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'bulkDelete-user', 'label' => 'Bulk Delete', 'guard_name' => 'users', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'import-user', 'label' => 'Import', 'guard_name' => 'users', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'export-user', 'label' => 'Export', 'guard_name' => 'users', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],

            ['name' => 'categories', 'label' => 'Category', 'guard_name' => 'root', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'view-category', 'label' => 'View', 'guard_name' => 'categories', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'show-category', 'label' => 'Show', 'guard_name' => 'categories', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'add-category', 'label' => 'Add', 'guard_name' => 'categories', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'edit-category', 'label' => 'Edit', 'guard_name' => 'categories', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'delete-category', 'label' => 'Delete', 'guard_name' => 'categories', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'bulkDelete-category', 'label' => 'Bulk Delete', 'guard_name' => 'categories', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'export-category', 'label' => 'Export', 'guard_name' => 'categories', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],

            ['name' => 'sub_categories', 'label' => 'Sub_category', 'guard_name' => 'root', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'view-sub_category', 'label' => 'View', 'guard_name' => 'sub_categories', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'show-sub_category', 'label' => 'Show', 'guard_name' => 'sub_categories', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'add-sub_category', 'label' => 'Add', 'guard_name' => 'sub_categories', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'edit-sub_category', 'label' => 'Edit', 'guard_name' => 'sub_categories', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'delete-sub_category', 'label' => 'Delete', 'guard_name' => 'sub_categories', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'bulkDelete-sub_category', 'label' => 'Bulk Delete', 'guard_name' => 'sub_categories', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'export-sub_category', 'label' => 'Export', 'guard_name' => 'sub_categories', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],

            ['name' => 'products', 'label' => 'Product', 'guard_name' => 'root', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'view-product', 'label' => 'View', 'guard_name' => 'products', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'show-product', 'label' => 'Show', 'guard_name' => 'products', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'add-product', 'label' => 'Add', 'guard_name' => 'products', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'edit-product', 'label' => 'Edit', 'guard_name' => 'products', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'delete-product', 'label' => 'Delete', 'guard_name' => 'products', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'bulkDelete-product', 'label' => 'Bulk Delete', 'guard_name' => 'products', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'export-product', 'label' => 'Export', 'guard_name' => 'products', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],

            ['name' => 'appointments', 'label' => 'Appointment', 'guard_name' => 'root', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'view-appointment', 'label' => 'View', 'guard_name' => 'appointments', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'show-appointment', 'label' => 'Show', 'guard_name' => 'appointments', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'add-appointment', 'label' => 'Add', 'guard_name' => 'appointments', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'edit-appointment', 'label' => 'Edit', 'guard_name' => 'appointments', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'delete-appointment', 'label' => 'Delete', 'guard_name' => 'appointments', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'bulkDelete-appointment', 'label' => 'Bulk Delete', 'guard_name' => 'appointments', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'import-appointment', 'label' => 'Import', 'guard_name' => 'appointments', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'export-appointment', 'label' => 'Export', 'guard_name' => 'appointments', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],

            ['name' => 'sms_templates', 'label' => 'Sms Template', 'guard_name' => 'root', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'view-sms-template', 'label' => 'View', 'guard_name' => 'sms_templates', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'show-sms-template', 'label' => 'Show', 'guard_name' => 'sms_templates', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'add-sms-template', 'label' => 'Add', 'guard_name' => 'sms_templates', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'edit-sms-template', 'label' => 'Edit', 'guard_name' => 'sms_templates', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'delete-sms-template', 'label' => 'Delete', 'guard_name' => 'sms_templates', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'bulkDelete-sms-template', 'label' => 'Bulk Delete', 'guard_name' => 'sms_templates', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'import-sms-template', 'label' => 'Import', 'guard_name' => 'sms_templates', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
            ['name' => 'export-sms-template', 'label' => 'Export', 'guard_name' => 'sms_templates', 'created_at' => config('constants.calender.date_time'), 'updated_at' => config('constants.calender.date_time')],
        ]);
    }
}
