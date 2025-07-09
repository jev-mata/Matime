<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
</script>

<template>

    <Head title="Invalid Invitation" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>
        <div class="p-5 flex items-center justify-center text-gray-200 text-center px-4">
            <div>
                <h1 class="text-3xl font-bold text-red-600 mb-4">Invalid or Expired Invitation</h1>
                <p class="text-gray-300 mb-6">The invitation link you followed is invalid or has already been used.</p>
                <a href="/" class="text-blue-600 underline">Return to homepage</a>
            </div>
        </div>
    </AuthenticationCard>
</template>
