<script setup lang="ts">
import { inject } from 'vue';
import { cn } from '@/lib/utils';

interface Props {
  value: string;
}

const props = defineProps<Props>();

const select = inject('select');

const handleClick = () => {
  select?.select(props.value);
};
</script>

<template>
  <div
    :class="
      cn(
        'relative flex w-full cursor-default select-none items-center rounded-sm py-1.5 pl-8 pr-2 text-sm outline-none hover:bg-accent hover:text-accent-foreground focus:bg-accent focus:text-accent-foreground'
      )
    "
    @click="handleClick"
  >
    <slot />
  </div>
</template> 