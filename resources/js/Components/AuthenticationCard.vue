<script setup lang="ts">
import { onMounted } from 'vue';
import { useTheme } from '@/utils/theme.js';

onMounted(async () => {
    useTheme();
});
</script>

<template>
    <div
        class="min-h-screen flex flex-col relative sm:justify-center items-center pt-6 bg-default-background bg-[url('/images/panso_bg.jpg')] bg-cover bg-left rounded">
        <!-- Centered Logo -->
        <div class="flex flex-col sm:justify-center  w-full items-center absolute" style="top:50%;left:50%; transform:translate(-50%,-50%)">
            <div class="flex justify-center w-full">
                <slot name="logo" />
            </div>

            <!-- Card -->
            <div
                class="w-full max-w-xs sm:max-w-md mt-6 px-6 py-4 shadow-md shadow-gray-800 overflow-hidden sm:rounded-lg"
                style="background-color: #101012">
                <slot />
            </div>

            <!-- Optional bottom actions -->
            <slot name="actions"></slot>
        </div>
    </div>
</template>
