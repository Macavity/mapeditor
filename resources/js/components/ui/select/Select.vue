<script setup lang="ts">
import { provide, ref } from 'vue';

interface Props {
  value?: string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  'update:value': [value: string];
}>();

const isOpen = ref(false);
const selectedValue = ref(props.value);

const open = () => {
  isOpen.value = true;
};

const close = () => {
  isOpen.value = false;
};

const select = (value: string) => {
  selectedValue.value = value;
  emit('update:value', value);
  close();
};

provide('select', {
  isOpen,
  selectedValue,
  open,
  close,
  select,
});
</script>

<template>
  <div class="relative">
    <slot />
  </div>
</template> 