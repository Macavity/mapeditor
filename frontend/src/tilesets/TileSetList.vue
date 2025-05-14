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
      <div class="col-md-4">
        <button 
          class="btn btn-danger btn-sm"
          @click="handleDelete(tileSet.uuid)"
          title="Delete tileset"
        >
          <i class="bi bi-trash"></i> Delete
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { useTileSetStore } from '@/tilesets/TileSetStore';

  const store = useTileSetStore();

  if (store.tileSets.length === 0) {
    console.log('TileSets empty => Trigger loading.');
    store.loadTileSets();
  }

  const handleDelete = async (uuid: string) => {
    if (confirm('Are you sure you want to delete this tileset?')) {
      await store.deleteTileSet(uuid);
    }
  };
</script>

<style scoped>
.tileset-list .row {
  padding: 0.5rem 0;
  border-bottom: 1px solid #eee;
}

.tileset-list .row:hover {
  background-color: #f8f9fa;
}
</style>
