<script setup lang="ts">
import { cn } from '@/lib/utils';
</script>

<template>
  <div :class="cn('text-sm [&_p]:leading-relaxed')">
    <slot />
  </div>
</template> 