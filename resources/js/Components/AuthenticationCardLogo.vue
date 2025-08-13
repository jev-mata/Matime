<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <Link
        :href="route('dashboard')"
        class="flex   items-center justify-center  gap-2 xl:text-6xl lg:text-6xl sm:text-5xl text-4xl font-bold py-2 dark:text-[#BFC7D6] text-black ">
        <img
            src="/panso.png"
            alt="Panso Logo"
            class="w-14 h-14 mx-auto  "/>
        Panso
    </Link>
</template>
