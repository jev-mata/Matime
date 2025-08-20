<script setup lang="ts">
import { computed } from 'vue';

const emit = defineEmits(['update:checked']);

const props = defineProps({
    checked: {
        type: [Array, Boolean],
        default: false,
    },
    value: {
        type: String,
        default: null,
    },
    id: {
        type: String,
        default: null,
    },
    class:{
        type: String,
        default: '',
    }
});

const proxyChecked = computed({
    get() {
        return props.checked;
    },

    set(val) {
        emit('update:checked', val);
    },
});
</script>

<template>
    <input
        :id="id"
        v-model="proxyChecked"
        type="checkbox"
        :value="value"
        :class="class"
        class="h-4 w-4 rounded dark:bg-[#7D88A1]  text-accent-500/80 focus:outline-none focus:ring-ring/50 focus-visible:outline-none focus-visible:ring-ring/50" />
</template>
