<script setup lang="ts">
import { BellAlertIcon, XMarkIcon } from '@heroicons/vue/20/solid';
import { SecondaryButton } from '@/packages/ui/src';
import { useStorage } from '@vueuse/core';
const showReleaseInfo = useStorage('showReleaseInfo-desktop', true);

 
</script>

<template>
    <div v-if="showReleaseInfo" class="py-4 hidden lg:block">
        <div
            class="rounded-lg px-2.5 py-2 bg-card-background border border-border-secondary">
            <div class="flex items-start justify-between">
                <div
                    class="text-xs pb-1.5 font-semibold text-text-tertiary flex items-center space-x-1">
                    <BellAlertIcon class="w-3.5"></BellAlertIcon>
                    <span> New Update </span>
                </div>
                <button>
                    <XMarkIcon
                        class="w-3.5 text-text-tertiary hover:text-text-secondary"
                        @click="showReleaseInfo = false"></XMarkIcon>
                </button>
            </div>

            <p class="text-xs">
                <span class="font-semibold">New Design</span> is
                here! Test it out and let us know what you think.
            </p> 
        </div>
    </div>
</template>

<style scoped></style>
