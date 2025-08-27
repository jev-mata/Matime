<script setup lang="ts">
import { twMerge } from 'tailwind-merge';

const props = defineProps<{
    value?: string;
    class?: string;
}>();
</script>

<template>
    <label
        :class="twMerge('block font-medium text-sm text-gray-800 dark:text-gray-300', props.class)">
        <span v-if="value" :class="twMerge('block font-medium text-sm ', props.class)">{{ value }}</span>
        <span v-else><slot /></span>
    </label>
</template>
