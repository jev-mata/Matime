<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

import { useTheme } from '@/utils/theme';
import NavLink from '@/Components/NavLink.vue';
defineProps({
    canResetPassword: Boolean,
    status: String,
});
useTheme();
const form = useForm({
    email: '',
    password: '',
    remember: true,
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        remember: form.remember ? 'on' : '',
    })).post(route('login'), {
        onFinish: () => form.reset('password'),
    });
}; 
</script>

<style scoped>
.not-found {
    text-align: center;
}
</style>
<template>

    <Head title="Page Not Found" />
    <div class=" w-full">
        <div class="p-5 text-2xl">
            <Link :href="route('dashboard')" class="font-bold text-text-primary py-2 ">
            PANSO
            </Link>
        </div>
        <div class="not-found w-full absolute h-full pt-20" style="left:50%; top:50%; transform: translate(-50%,-50%);">

            <div class="w-full h-full relative">

                <div class="p-5 absolute w-full " style="left:50%; top:40%; transform: translate(-50%,-50%);">
                    <h1 class="text-8xl py-5">404</h1>
                    <h1 class="text-5xl py-5">Oops! Page Not Found</h1>
                    <p class="py-4">We can't seem to find the page you're looking for.</p>
                    <div class="actions">
                        <Link :href="route('dashboard')"
                            class="btn btn-primary hover:text-gray-400 font-bold underline">Go
                        to Home</Link>
                    </div>
                </div>
            </div>

        </div>
    </div>

</template>
