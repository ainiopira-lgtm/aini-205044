<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('status')->default('active')->change();
            $table->string('photo')->nullable();
        });
        DB::table('members')
            ->whereNotIn('status', ['active', 'inactive'])
            ->update(['status' => 'active']);

        Schema::create('member_number_sequences', function (Blueprint $table) {
            $table->unsignedSmallInteger('year')->primary();
            $table->unsignedInteger('last_number')->default(0);
        });
    }

    public function down(): void
    {
        DB::table('members')->where('status', 'inactive')->update(['status' => 'active']);
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('photo');
            $table->enum('status', ['active', 'returned', 'overdue'])->default('active')->change();
        });
        Schema::dropIfExists('member_number_sequences');
    }
};
