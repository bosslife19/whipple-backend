<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('can_access_admin')->default(false)->after('remember_token');
            $table->string('admin_role', 32)->nullable()->after('can_access_admin');
            $table->json('admin_permissions')->nullable()->after('admin_role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['can_access_admin', 'admin_role', 'admin_permissions']);
        });
    }
};
