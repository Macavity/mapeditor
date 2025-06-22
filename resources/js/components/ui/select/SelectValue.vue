<script setup lang="ts">
import { inject } from 'vue';

const select = inject('select');
</script>

<template>
  <span class="block truncate">
    <slot :value="select?.selectedValue">
      {{ select?.selectedValue || 'Select an option' }}
    </slot>
  </span>
</template> 