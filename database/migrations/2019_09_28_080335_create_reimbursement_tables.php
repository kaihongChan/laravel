<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReimbursementTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 报销类型
        Schema::create('reimbursement_types', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('自增id');
            $table->string('name')->comment('名称');
            $table->text('description')->nullable()->comment('描述');
            $table->tinyInteger('status')->default(1)->comment('状态：0禁用，1启用');
            $table->timestamps();
        });

        // 费用类型
        Schema::create('reimbursement_projects', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('自增id');
            $table->string('name')->comment('名称');
            $table->text('description')->nullable()->comment('描述');
            $table->tinyInteger('status')->default(1)->comment('状态：0禁用，启用');
            $table->timestamps();
        });

        // 报销申请
        Schema::create('reimbursements', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('自增id');
            $table->string('name')->comment('名称');
            $table->unsignedBigInteger('type_id')->comment('报销类型id');
            $table->string('amount')->comment('报销金额');
            $table->string('created_by')->comment('起草人');
            $table->string('remarks')->nullable()->comment('备注');
            $table->unsignedBigInteger('current_node')->nullable()->comment('当前节点');
            $table->tinyInteger('status')->default(0)->comment('状态：0待提审，1流转中，2审核结束');
            $table->integer('apply_times')->default(0)->comment('提交次数');
            $table->timestamps();
        });

        // 审批单附件
        Schema::create('reimbursement_attachments', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('自增id');
            $table->unsignedBigInteger('reimbursement_id')->comment('关联审批单id');
            $table->string('name')->comment('名称');
            $table->string('file_type')->comment('文件类型');
            $table->string('file_path')->comment('附件存储路径');
            $table->text('remarks')->nullable()->comment('备注');
            $table->timestamps();
        });

        // 报销明细清单
        Schema::create('reimbursement_details', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('自增id');
            $table->unsignedBigInteger('reimbursement_id')->comment('申请id');
            $table->unsignedBigInteger('project_id')->comment('报销项目id');
            $table->date('date')->comment('发生日期');
            $table->string('amount')->comment('金额');
            $table->string('remarks')->nullable()->comment('费用说明');
            $table->unsignedBigInteger('created_by')->comment('起草人');
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
        Schema::dropIfExists('reimbursement_types');
        Schema::dropIfExists('reimbursement_projects');
        Schema::dropIfExists('reimbursement');
        Schema::dropIfExists('reimbursement_attachments');
        Schema::dropIfExists('reimbursement_details');
    }
}
