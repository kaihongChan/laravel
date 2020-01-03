<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Base extends Model
{
    /**
     * 错误信息
     *
     * @var string
     */
    protected $error = '';

    /**
     * 获取错误信息
     *
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }
}