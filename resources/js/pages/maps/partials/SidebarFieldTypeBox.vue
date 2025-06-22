<script setup lang="ts">
import SidebarSelectableList from '@/components/ui/SidebarSelectableList.vue';
import { useEditorStore } from '@/stores/editorStore';
import { computed, onMounted } from 'vue';

const editorStore = useEditorStore();

const fieldTypesLoaded = computed(() => editorStore.fieldTypes.length > 0);

onMounted(async () => {
    try {
        await editorStore.loadFieldTypes();
    } catch (error) {
        console.error('Failed to load field types:', error);
    }
});

const selectFieldType = (fieldType: any) => {
    editorStore.selectFieldType(fieldType);
};
</script>

<template>
    <SidebarSelectableList
        title="Field Types"
        :items="editorStore.fieldTypes"
        :selected-item="editorStore.selectedFieldType"
        :loading="!fieldTypesLoaded"
        empty-message="No field types available"
        empty-sub-message="Create field types in the Field Types management page"
        selected-info-message="Click on the map to place this field type"
        @select="selectFieldType"
    />
</template>
