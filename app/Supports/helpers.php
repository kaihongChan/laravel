<?php

if (!function_exists('formatMenuTree')) {
    /**
     * 树形菜单生成
     *
     * @param $menus ['menuId' => ['id'=> 1 ,pid=> 0, 'name'=> '']]
     * @return array
     */
    function formatMenuTree(array $menus)
    {
        $formatMenus = [];
        foreach ($menus as $value) {
            if (isset($menus[$value['pid']])) {
                $menus[$value['pid']]['children'][] = &$menus[$value['id']];
            } else {
                $formatMenus[] = &$menus[$value['id']];
            }
        }

        return $formatMenus;
    }
}

if (!function_exists('formatOperator')) {
    /**
     * 格式化运算符
     *
     * @param string $operator
     * @return string
     */
    function formatOperator(string $operator)
    {
        switch ($operator) {
            case '=' :
                break;
            default:
                break;
        }
        return $operator;
    }
}