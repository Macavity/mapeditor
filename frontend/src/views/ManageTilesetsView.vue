<script setup lang="ts">
  import TileSetList from "@/tilesets/TileSetList.vue";
</script>

<template>
  <div class="manage-tilesets">
    <h1>Manage Tilesets</h1>
    <TileSetList />
  </div>
</template>

<script lang="ts">
  export default {
    computed: {
      tilesets() {
        return [];
      },
    },
  };
</script>

<style></style>
