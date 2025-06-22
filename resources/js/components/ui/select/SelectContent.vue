<script setup lang="ts">
import { inject } from 'vue';
import { cn } from '@/lib/utils';

const select = inject('select');
</script>

<template>
  <div
    v-if="select?.isOpen"
    :class="
      cn(
        'absolute top-full z-50 w-full rounded-md border bg-popover text-popover-foreground shadow-md animate-in fade-in-0 zoom-in-95'
      )
    "
  >
    <div class="max-h-60 overflow-auto p-1">
      <slot />
    </div>
  </div>
</template> 