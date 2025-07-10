<?php
function timesheet_helper()
{
    return 'Working!';
}
if (!function_exists('register_module_menu')) {
    function register_module_menu(array $items)
    {
        app('module_menus')->push(...$items);
    }
} 
