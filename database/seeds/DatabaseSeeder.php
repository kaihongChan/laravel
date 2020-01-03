<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // $this->call(UsersTableSeeder::class);
        DB::table('users')->insert([
            'name' => 'admin',
            'email' => '291560152@qq.com',
            'password' => bcrypt('123456'),
            'nickname' => '超级管理员',
        ]);
    }
}
