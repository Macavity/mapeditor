<script setup lang="ts">
import { inject } from 'vue';
import { cn } from '@/lib/utils';
import { ChevronDown } from 'lucide-vue-next';

const select = inject('select');

const handleClick = () => {
  select?.open();
};
</script>

<template>
  <button
    type="button"
    :class="
      cn(
        'flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50'
      )
    "
    @click="handleClick"
  >
    <slot />
    <ChevronDown class="h-4 w-4 opacity-50" />
  </button>
</template> 