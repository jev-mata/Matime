<script setup lang="ts">
import type { HtmlButtonType } from '@/types/dom'; 
import LoadingSpinner from '../LoadingSpinner.vue';
import type { Component } from 'vue'; 

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
           class="inline-flex items-center px-2  sm:px-3 py-1 sm:py-2 bg-red-600  rounded-md text-white hover:bg-red-500 active:bg-red-700 font-medium text-xs sm:text-sm text-button-primary-text  active:bg-button-primary-background-hover focus:outline-none focus-visible:ring-2 focus-visible:border-transparent focus-visible:ring-ring transition ease-in-out duration-150">
      
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
