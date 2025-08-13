<script setup lang="ts">
import { cn } from '@/lib/utils'
import {
  DialogContent,
  type DialogContentEmits,
  type DialogContentProps,
  DialogOverlay,
  DialogPortal,
  useForwardPropsEmits,
} from 'reka-ui'
import { computed, type HTMLAttributes } from 'vue'

const props = defineProps<DialogContentProps & { class?: HTMLAttributes['class'] }>()
const emits = defineEmits<DialogContentEmits>()

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props

  return delegated
})

const forwarded = useForwardPropsEmits(delegatedProps, emits)
</script>

<template>
  <DialogPortal>
    <DialogOverlay
      class="fixed inset-0 z-50 backdrop-blur-sm data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0">
      <div class="absolute inset-0  dark:bg-[#13192B] bg-[#E0E3E5] dark:text-[#BFC7D6]  opacity-30" />
    </DialogOverlay>
    <DialogContent v-bind="forwarded" :class="cn(
      'fixed top-0 left-0 z-50 w-screen h-screen flex items-start pt-6 md:pt-20 xl:pt-32 justify-center overflow-auto data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95',

    )">
      <div :class="cn(
        ' dark:bg-[#13192B]  bg-white dark:text-[#BFC7D6] grid w-full max-w-lg border border-[#303F61] shadow-lg duration-200 sm:rounded-lg',
        props.class,
      )">
        <slot />
      </div>
    </DialogContent>
  </DialogPortal>
</template>
