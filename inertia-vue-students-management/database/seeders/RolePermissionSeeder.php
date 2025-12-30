<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roleId = 1; // Super Admin

        // Fetch all permission IDs
        $permissionIds = DB::table('tbl_permissions')->pluck('id');

        // Prepare insert data
        $data = [];
        $now = now();

        foreach ($permissionIds as $permissionId) {
            $data[] = [
                'role_id'       => $roleId,
                'permission_id' => $permissionId,
                'is_active'    => 1,
                'created_by'    => 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ];
        }

        /**
         * Remove existing permissions for Super Admin
         * (Prevents duplicates and keeps it idempotent)
         */
        DB::table('tbl_role_permission')
            ->where('role_id', $roleId)
            ->delete();

        // Insert fresh permissions
        DB::table('tbl_role_permission')->insert($data);
    }
}
