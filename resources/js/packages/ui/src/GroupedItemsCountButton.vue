<script setup lang="ts">
import { twMerge } from 'tailwind-merge';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        expanded?: boolean;
        size?: string;
    }>(),
    {
        expanded: false,
        size: 'w-5 h-5',
    }
);

const expandedStatusClasses = computed(() => {
    if (props.expanded) {
        return 'dark:border-[#0C101E] dark:bg-[#0C101E] bg-[#E0E3E6] ';
    }
    return 'dark:border-[#0C101E] dark:bg-[#0C101E] bg-[#E0E3E6] hover:bg-card-background-active hover:transition text-text-secondary';
});
</script>

<template>
    <button
        :class="
            twMerge(
                'ml-2 font-medium rounded flex text-xs items-center transition justify-center focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:border-transparent',
                expandedStatusClasses,
                props.size
            )
        ">
        <slot></slot>
    </button>
</template>

<style scoped></style>
