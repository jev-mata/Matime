<script setup lang="ts">
import type { HtmlButtonType } from '@/types/dom'; 
import LoadingSpinner from '../LoadingSpinner.vue';
import type { Component } from 'vue';
import { twMerge } from 'tailwind-merge';

const props = withDefaults(

    defineProps<{
        type?: HtmlButtonType;
        icon?: Component;
        loading?: boolean;
    }>(),
    {
        type: 'submit',
        loading: false,
    }
);
</script>
<template>
    <button
        :type="type"
        :disabled="loading"
        class="inline-flex items-center justify-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
        
            <LoadingSpinner v-if="loading"></LoadingSpinner>
            <component
                :is="props.icon"
                v-if="props.icon && !loading"
                class="w-4 -ml-0.5 mr-1"></component>
            <span>
                <slot />
            </span>
    </button>
</template>
