<script setup lang="ts">

import InputLabel from "../../../packages/ui/src/Input/InputLabel.vue";
import FormSection from "@/Components/FormSection.vue";
import { usePreferredColorScheme } from '@vueuse/core';
import { themeSetting } from "@/utils/theme";

const preferredColor = usePreferredColorScheme()

const appName = import.meta.env.VITE_APP_NAME ?? 'this system';
</script>

<template>
    <FormSection>
        <template #title> Theme</template>

        <template #description>
            Choose how you want {{ appName }} to look on your device
        </template>

        <template #form>

            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="theme" value="Theme" />
                <select id="theme" v-model="themeSetting" name="theme" required
                    class="mt-1 block w-full border-input-border  dark:bg-[#0C101E]  dark:border-[#3F4961] text-text-primary focus:border-input-border-active rounded-md shadow">
                    <option value="system">System</option>
                    <option value="light">Light</option>
                    <option value="dark">Dark</option>
                </select>
                <p v-if="themeSetting === 'system'" class="w-full text-sm text-text-tertiary font-medium mt-2">
                    System default: {{ preferredColor }}
                </p>
            </div>
        </template>
    </FormSection>

</template>

<style scoped></style>
