<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <Link
        :href="route('dashboard')"
        class="text-center xl:text-8xl lg:text-8xl sm:text-6xl text-6xl font-bold py-2 text-white">
        PANSO
    </Link>
</template>
