<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';

interface Props {
  value?: number;
  max?: number;
}

const props = withDefaults(defineProps<Props>(), {
  value: 0,
  max: 100,
});

const percentage = computed(() => {
  return Math.min(Math.max((props.value / props.max) * 100, 0), 100);
});
</script>

<template>
  <div
    :class="
      cn(
        'relative h-4 w-full overflow-hidden rounded-full bg-secondary'
      )
    "
  >
    <div
      :class="
        cn(
          'h-full w-full flex-1 bg-primary transition-all'
        )
      "
      :style="{ transform: `translateX(-${100 - percentage}%)` }"
    />
  </div>
</template> 