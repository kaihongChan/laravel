<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDepartmentsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 部门
        Schema::create('departments', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('自增id');
            $table->unsignedBigInteger('pid')->default(0)->comment('父级id');
            $table->string('name')->comment('部门名称');
            $table->timestamps();
        });

        // 部门-用户
        Schema::create('department_users', function (Blueprint $table) {
            $table->unsignedBigInteger('dept_id')->comment('部门id');
            $table->unsignedBigInteger('user_id')->comment('用户id');
            $table->boolean('is_manager')->default(0)->comment('主管标识');
            $table->primary(['dept_id', 'user_id'], 'dept_users_primary');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('departments');
        Schema::dropIfExists('department_users');
    }
}
