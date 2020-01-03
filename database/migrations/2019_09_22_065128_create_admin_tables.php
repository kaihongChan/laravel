<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAdminTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 系统权限
        Schema::create('permissions', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('自增id');
            $table->string('name')->comment('名称');
            $table->string('route_name')->comment('路由');
            $table->text('description')->nullable()->comment('描述');
            $table->timestamps();
        });

        // 权限策略
        Schema::create('policies', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('自增id');
            $table->string('identify')->unique()->comment('唯一标识');
            $table->string('name')->comment('名称');
            $table->text('description')->nullable()->comment('描述');
            $table->timestamps();
        });

        // 系统菜单
        Schema::create('menus', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('自增id');
            $table->unsignedBigInteger('pid')->comment('父级id');
            $table->string('name')->comment('名称');
            $table->tinyInteger('type')->default(0)->comment('类型：0节点，1路由菜单');
            $table->string('icon')->nullable()->comment('图标');
            $table->string('link')->nullable()->comment('链接');
            $table->string('i18n')->comment('国际化标记');
            $table->integer('sort')->default(0)->comment('排序号');
            $table->timestamps();
        });

        // 系统角色
        Schema::create('roles', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('自增id');
            $table->string('identify')->unique()->comment('唯一标识');
            $table->string('name')->comment('名称');
            $table->text('description')->nullable()->comment('描述');
            $table->tinyInteger('status')->default(1)->comment('状态：0禁用，1启用');
            $table->timestamps();
        });

        // 系统用户
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('自增id');
            $table->string('name')->unique()->comment('用户名')->collation('utf8mb4_bin');
            $table->string('password')->comment('密码');
            $table->string('nickname')->nullable()->comment('昵称');
            $table->string('avatar')->nullable()->comment('头像');
            $table->tinyInteger('status')->default(1)->comment('状态：0禁用，1启用');
            $table->string('mobile', 11)->unique()->nullable()->comment('手机号');
            $table->string('email')->unique()->nullable()->comment('邮箱');
            $table->timestamp('email_verified_at')->nullable()->comment('邮箱验证标记');
            $table->rememberToken()->comment('');
            $table->timestamps();
        });

        // 策略-权限
        Schema::create('policy_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('policy_id')->comment('策略id');
            $table->unsignedBigInteger('permission_id')->comment('权限id');
            $table->timestamps();
            $table->primary(['policy_id', 'permission_id']);
        });

        // 角色-策略
        Schema::create('role_policies', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->comment('角色id');
            $table->unsignedBigInteger('policy_id')->comment('策略id');
            $table->timestamps();
            $table->primary(['role_id', 'policy_id']);
        });

        // 角色-菜单
        Schema::create('role_menus', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->comment('角色id');
            $table->unsignedBigInteger('menu_id')->comment('菜单id');
            $table->timestamps();
            $table->primary(['role_id', 'menu_id']);
        });

        // 用户-角色
        Schema::create('user_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->comment('用户id');
            $table->unsignedBigInteger('role_id')->comment('角色id');
            $table->timestamps();
            $table->primary(['user_id', 'role_id']);
        });

        // 用户-策略
        Schema::create('user_policies', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->comment('用户id');
            $table->unsignedBigInteger('policy_id')->comment('策略id');
            $table->timestamps();
            $table->primary(['user_id', 'policy_id']);
        });

        // 用户-菜单
        Schema::create('user_menus', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->comment('用户id');
            $table->unsignedBigInteger('menu_id')->comment('菜单id');
            $table->timestamps();
            $table->primary(['user_id', 'menu_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('policies');
        Schema::dropIfExists('menus');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');
        Schema::dropIfExists('policy_permissions');
        Schema::dropIfExists('role_policies');
        Schema::dropIfExists('role_menus');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('user_policies');
        Schema::dropIfExists('user_menus');
    }
}
