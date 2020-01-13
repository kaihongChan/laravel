<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkflow extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 审核流程
        Schema::create('workflow', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('自增id');
            $table->string('model')->unique()->comment('适用模型');
            $table->string('name')->comment('名称');
            $table->text('description')->nullable()->comment('描述');
            $table->timestamps();
        });

        // 审核流程节点
        Schema::create('workflow_nodes', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('自增id');
            $table->unsignedBigInteger('workflow_id')->comment('审核流id');
            $table->string('name')->comment('名称');
            $table->tinyInteger('type')->comment('节点类型：0常规节点，1开始节点，2结束节点');
            $table->tinyInteger('mode')->default(0)->comment('审核方式：0普签，1会签');
            $table->integer('position_x')->default(0)->comment('节点x坐标');
            $table->integer('position_y')->default(0)->comment('节点y坐标');
            $table->timestamps();
        });

        // 审核流程节点连线
        Schema::create('workflow_node_edges', function (Blueprint $table) {
            $table->unsignedBigInteger('source')->comment('当前节点');
            $table->unsignedBigInteger('target')->comment('下一节点');
            $table->text('condition')->nullable()->comment('连线条件');
            $table->string('label')->nullable()->comment('标签');
            $table->timestamps();
            $table->primary(['source', 'target'], 'source_target_primary');
        });

        // 节点-角色
        Schema::create('workflow_node_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('node_id')->comment('节点id');
            $table->unsignedBigInteger('role_id')->comment('角色id');
            $table->timestamps();
            $table->primary(['node_id', 'role_id'], 'node_role_primary');
        });

        // 节点-部门
        Schema::create('workflow_node_departments', function (Blueprint $table) {
            $table->unsignedBigInteger('node_id')->comment('节点id');
            $table->unsignedBigInteger('dept_id')->comment('部门id');
            $table->timestamps();
            $table->primary(['node_id', 'dept_id'], 'node_dept_primary');
        });

        // 节点-用户
        Schema::create('workflow_node_users', function (Blueprint $table) {
            $table->unsignedBigInteger('node_id')->comment('节点id');
            $table->unsignedBigInteger('user_id')->comment('用户id');
            $table->timestamps();
            $table->primary(['node_id', 'user_id'], 'node_user_primary');
        });

        // 审批单流转日志
        Schema::create('workflow_logs', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('记录id');
            $table->unsignedBigInteger('record_id')->comment('记录id');
            $table->string('model')->comment('适用模型');
            $table->integer('apply_times')->default(0)->comment('提交次数');
            $table->unsignedBigInteger('node_id')->comment('节点id');
            $table->string('node_name')->comment('节点名称');
            $table->tinyInteger('node_mode')->comment('节点类型（冗余字段，记录节点审批类型）');
            $table->string('action')->comment('操作：submit：提交审核，pass：通过，reject：驳回');
            $table->text('remarks')->nullable()->comment('备注');
            $table->unsignedBigInteger('created_by')->comment('审核人');
            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('workflow');
        Schema::dropIfExists('workflow_nodes');
        Schema::dropIfExists('workflow_node_lines');
        Schema::dropIfExists('workflow_node_roles');
        Schema::dropIfExists('workflow_node_users');
        Schema::dropIfExists('workflow_logs');
    }
}
