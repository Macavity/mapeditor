<template>
  <div class="tileset-list">
    <div class="row">
      <div class="col-md-4"><strong>Name</strong></div>
      <div class="col-md-2"><strong>Count Tiles</strong></div>
      <div class="col-md-4"><strong>Actions</strong></div>
    </div>
    <div class="row" v-for="(tileSet, i) in store.tileSets" :key="i">
      <div class="col-md-4">
        {{ tileSet.name }}
      </div>
      <div class="col-md-2">
        {{ tileSet.tileCount }}
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { useTileSetStore } from "@/tilesets/TileSetStore";

  const store = useTileSetStore();
  console.log("store", store);

  if (store.tileSets.length === 0) {
    console.log("TileSets empty => Trigger loading.");
    store.loadTileSets();
  }
</script>

<script lang="ts"></script>
