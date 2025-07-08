<?php

if (!function_exists('register_module_menu')) {
    function register_module_menu(array $items)
    {
        app('module_menus')->push(...$items);
    }


    register_module_menu([
        [
            'title' => 'Approval',
            'icon' => 'HandThumbUpIcon', // must match a key in your frontend iconMap
            'route' => 'approval.index',
            'href' => '/time/approval',
            'show' => true,
            'role' => ['manager']
        ],


    ]);
}
