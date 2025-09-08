<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('perencanaan_data', function (Blueprint $table) {
            $table->string('region_code')->nullable()->after('doc_berita_acara_validasi');
            $table->integer('period_year')->nullable()->after('region_code');
            $table->string('city_code')->nullable()->after('period_year');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('perencanaan_data', function (Blueprint $table) {
            $table->dropColumn(['region_code', 'period_year', 'city_code']);
        });
    }
};
