<script setup lang="ts">
  import { useRoute } from 'vue-router';
  import { useEditorStore } from '@/editor/EditorStore';
  import Editor from '@/editor/Editor.vue';

  const route = useRoute();
  const store = useEditorStore();

  const uuid = route.params.uuid.toString();
  store.loadMap(uuid);
</script>

<template>
  <div class="map-edit-view">
    <Editor v-if="store.map" :map="store.map" />
  </div>
</template>
