<script setup lang="ts">
import SidebarSelectableList from '@/components/ui/SidebarSelectableList.vue';
import { useObjectTypeStore } from '@/stores/objectTypeStore';
import { computed } from 'vue';

const objectTypeStore = useObjectTypeStore();

function selectObjectType(objectType: any) {
    objectTypeStore.activateObjectType(objectType.id);
}

// Convert undefined to null for the component
const selectedObjectType = computed(() => objectTypeStore.activeObjectType || null);
</script>

<template>
    <SidebarSelectableList
        title="Objects"
        :items="objectTypeStore.objectTypes"
        :selected-item="selectedObjectType"
        :loading="objectTypeStore.loading"
        empty-message="No object types available"
        empty-sub-message="Create object types in the Object Types management page"
        selected-info-message="Click on the canvas to place objects"
        @select="selectObjectType"
    />
</template>

<style lang="scss" scoped>
.object-preview {
    position: relative;
    height: 200px;
    overflow: auto;
    background-color: #fff;
}
</style>
