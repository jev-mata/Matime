<script setup lang="ts">
import {
    Dialog,
    DialogContent,
    DialogFooter,
} from '@/Components/ui/dialog'
import { computed } from 'vue'

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['close']);

const close = () => {
    if (props.closeable) {
        emit('close');
    }
};

const maxWidthClass = computed(() => {
    return {
        sm: 'sm:max-w-sm',
        md: 'sm:max-w-md',
        lg: 'sm:max-w-lg',
        xl: 'sm:max-w-xl',
        '2xl': 'sm:max-w-2xl',
        '3xl': 'sm:max-w-3xl',
        '4xl': 'sm:max-w-4xl',
        '5xl': 'sm:max-w-5xl',
        '6xl': 'sm:max-w-6xl',
        '7xl': 'sm:max-w-7xl',
        full: 'sm:max-w-full',
    }[props.maxWidth] ?? 'sm:max-w-2xl';
});
</script>

<template>
    <Dialog :open="show" @update:open="close">
        <DialogContent :class="maxWidthClass">

            <div>
                <slot />
            </div>

            <DialogFooter>
                <slot name="footer" />
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
