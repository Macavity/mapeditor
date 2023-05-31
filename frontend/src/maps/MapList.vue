<template>
  <div class="map-list" v-if="store.maps.length">
    <div class="row p-2">
      <div class="col-md-4"><strong>Name</strong></div>
      <div class="col-md-4"><strong>Actions</strong></div>
    </div>
    <div class="row p-2" v-for="(map, i) in store.maps" :key="i">
      <div class="col-md-4">
        {{ map.name }}
      </div>
      <div class="col-md-2">
        <button
          type="button"
          @click="goToMapEdit(map.uuid)"
          class="btn btn-primary"
        >
          Edit
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { RouteFactory } from '@/router/RouteFactory';
  import { useRouter } from 'vue-router';
  import { useMapStore } from '@/maps/MapStore';

  const router = useRouter();
  const goToMapEdit = (uuid: string) =>
    router.push(RouteFactory.toMapEdit(uuid));

  const store = useMapStore();

  if (!store.loaded) {
    console.log('Maps not loaded => Trigger loading.');
    store.loadMaps();
  }
</script>
