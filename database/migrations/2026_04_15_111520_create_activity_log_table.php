<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivityLogTable extends Migration
{
    public function up()
    {
        Schema::connection(config('activitylog.database_connection'))->create(config('activitylog.table_name'), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('log_name')->nullable();
            $table->text('description');

            // الحل هنا: نستخدم string بدلاً من uuid أو morphs
            // لكي يستوعب العمود رقم المنتج (1) و UUID اليوزر في نفس الوقت
            $table->string('subject_id')->nullable();
            $table->string('subject_type')->nullable();

            $table->string('causer_id')->nullable();
            $table->string('causer_type')->nullable();

            $table->json('properties')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->timestamps();

            $table->index(['subject_id', 'subject_type'], 'subject_index');
            $table->index(['causer_id', 'causer_type'], 'causer_index');
        });
    }

    public function down()
    {
        Schema::connection(config('activitylog.database_connection'))->dropIfExists(config('activitylog.table_name'));
    }
}
