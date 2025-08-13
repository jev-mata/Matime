import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";
import typography from "@tailwindcss/typography";

/** @type {import("tailwindcss").Config} */
export default {
    darkMode:   "class" ,
    content: [
        "./extensions/Invoicing/resources/js/**/*.vue",
        "./extensions/Timesheet/resources/js/**/*.vue",
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./vendor/laravel/jetstream/**/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        './extensions/**/resources/**/*.vue',
        "./resources/js/**/*.vue"
    ],
    theme: { 
    },

    plugins: [forms, typography, require("@tailwindcss/container-queries"), require("tailwindcss-animate")]
};
