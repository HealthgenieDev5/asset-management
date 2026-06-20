<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_subcategories', fn(Blueprint $t) => $t->index('asset_category_id'));
        Schema::table('assets', fn(Blueprint $t) => $t->index('asset_subcategory_id'));
        Schema::table('assets', fn(Blueprint $t) => $t->index('deleted_at'));
        Schema::table('asset_services', fn(Blueprint $t) => $t->index('deleted_at'));
        Schema::table('asset_complaints', fn(Blueprint $t) => $t->index('deleted_at'));
        Schema::table('asset_warranties', fn(Blueprint $t) => $t->index('expiry_date'));
        Schema::table('asset_warranties', fn(Blueprint $t) => $t->index('status'));
        Schema::table('asset_complaint_details', fn(Blueprint $t) => $t->index('asset_complaint_id'));
    }

    public function down(): void
    {
        Schema::table('asset_subcategories', fn(Blueprint $t) => $t->dropIndex(['asset_category_id']));
        Schema::table('assets', fn(Blueprint $t) => $t->dropIndex(['asset_subcategory_id']));
        Schema::table('assets', fn(Blueprint $t) => $t->dropIndex(['deleted_at']));
        Schema::table('asset_services', fn(Blueprint $t) => $t->dropIndex(['deleted_at']));
        Schema::table('asset_complaints', fn(Blueprint $t) => $t->dropIndex(['deleted_at']));
        Schema::table('asset_warranties', fn(Blueprint $t) => $t->dropIndex(['expiry_date']));
        Schema::table('asset_warranties', fn(Blueprint $t) => $t->dropIndex(['status']));
        Schema::table('asset_complaint_details', fn(Blueprint $t) => $t->dropIndex(['asset_complaint_id']));
    }
};
