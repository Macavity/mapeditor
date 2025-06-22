<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    icon?: any;
    text?: string;
    active?: boolean;
    variant?: 'secondary' | 'primary';
    disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    active: false,
    variant: 'secondary',
    disabled: false,
});

const emit = defineEmits<{
    click: [event: MouseEvent];
}>();

const buttonClasses = computed(() => {
    const baseClasses = 'flex items-center gap-2 rounded-lg border px-4 py-2 transition-colors';
    
    if (props.disabled) {
        return `${baseClasses} opacity-50 cursor-not-allowed border-gray-300 text-gray-500`;
    }
    
    if (props.variant === 'primary') {
        return `${baseClasses} ${
            props.active
                ? 'bg-primary text-primary-foreground border-primary'
                : 'border-primary text-primary hover:bg-primary/10'
        }`;
    }
    
    // secondary variant (default)
    return `${baseClasses} ${
        props.active
            ? 'bg-secondary text-secondary-foreground border-secondary'
            : 'border-secondary text-secondary hover:bg-secondary/10'
    }`;
});

const handleClick = (event: MouseEvent) => {
    if (!props.disabled) {
        emit('click', event);
    }
};
</script>

<template>
    <button
        type="button"
        :class="buttonClasses"
        :disabled="disabled"
        @click="handleClick"
    >
        <component v-if="icon" :is="icon" class="h-4 w-4" />
        <span v-if="text">{{ text }}</span>
        <slot />
    </button>
</template> 