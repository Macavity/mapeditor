<script setup lang="ts">
  import MapEdit from '@/maps/MapEdit.vue';
  import { useRoute } from 'vue-router';
  import { useMapStore } from '@/maps/MapStore';

  const route = useRoute();
  const store = useMapStore();

  const uuid = route.params.uuid.toString();
  store.loadMap(uuid);
</script>

<template>
  <div class="manage-maps">
    <h1>Edit Map</h1>

    <MapEdit v-if="store.map" :map="store.map" />
  </div>
</template>
