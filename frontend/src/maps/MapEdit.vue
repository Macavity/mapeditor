<template>
  <div class="map-list" v-if="store.maps.length">
    <div class="row">
      <div class="col-md-4"><strong>Name</strong></div>
      <div class="col-md-4"><strong>Actions</strong></div>
    </div>
    <div class="row" v-for="(map, i) in store.maps" :key="i">
      <div class="col-md-4">
        {{ map.name }}
      </div>
      <div class="col-md-2"></div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { useMapStore } from '@/maps/MapStore';

  const store = useMapStore();

  if (!store.loaded) {
    console.log('Maps not loaded => Trigger loading.');
    store.loadMaps();
  }
</script>
