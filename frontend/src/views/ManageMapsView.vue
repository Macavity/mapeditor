<script setup lang="ts">
  import MapList from '../maps/MapList.vue';
  import CreateMapPanel from '../maps/CreateMapPanel.vue';
</script>

<template>
  <div class="manage-maps">
    <h1>Manage Maps</h1>

    <CreateMapPanel />

    <MapList />
  </div>
</template>
