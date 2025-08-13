<script setup lang="ts">
import { TabsTrigger } from '@/Components/ui/tabs'
import { twMerge } from 'tailwind-merge'
import type { Component } from 'vue'

const props = defineProps<{
    value: string
    class?: string
    icon?: Component
}>()
</script>

<template>
    <TabsTrigger
        :value="value"
        :icon="icon"
        :class="twMerge('rounded-md px-2 sm:px-3 py-1   sm:py-1.5 text-xs sm:text-sm font-medium text-[#636E88] hover:text-text-primary focus-visible:outline-none data-[state=active]:dark:bg-[#303F61]  data-[state=active]:text-[#BFC7D6]   hover:dark:bg-[#303F61] hover:dark:text-gray-100 dark:bg-[#0C101E]', props.class)">
        <slot></slot>
    </TabsTrigger>
</template>

<style scoped></style>
