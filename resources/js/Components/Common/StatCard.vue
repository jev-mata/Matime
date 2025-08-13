<script setup lang="ts">
defineProps<{
    title: string;
    value?: string;
}>();
</script>

<template>
    <div
        class="rounded-lg dark:bg-[#13192B]  bg-white dark:border-[#13192B] shadow border px-3.5 py-2.5">
        <dt class="font-semibold text-sm text-text-secondary">{{ title }}</dt>
        <dd class="text-2xl text-text-primary pt-1 font-semibold">
            {{ value ?? '--' }}
        </dd>
    </div>
</template>

<style scoped></style>
