<script setup lang="ts">
  import { CreateMapDto } from '@/maps/dtos/CreateMap.dto';
  import { MapService } from '@/maps/MapService';
  import { useToast } from 'vue-toast-notification';
  import { useRouter } from 'vue-router';
  import { ref } from 'vue';

  const router = useRouter();
  const name = ref('');
  const width = ref(20);
  const height = ref(20);
  const tileSize = ref(32);

  async function createMap() {
    const newMap = new CreateMapDto(
      name.value,
      width.value,
      height.value,
      tileSize.value,
      tileSize.value
    );

    MapService.createMap(newMap)
      .then((map) => {
        router.push({ name: 'map-edit', params: { id: map.uuid } });
      })
      .catch((error) => {
        console.error(error);
        useToast().error(
          'Failed to create new Map: ' +
            error.response.data.message.join('<br>'),
          { duration: 10000 }
        );
      });
  }
</script>

<template>
  <div class="card mb-3">
    <div class="card-header">Create New Map</div>
    <div class="card-body">
      <div class="card-text">
        <div class="form-group mb-2">
          <label>Name</label>
          <input v-model="name" type="text" class="form-control" />
        </div>
        <div class="form-group mb-2">
          <label>Fields per Row (Width)</label>
          <input v-model.number="width" type="text" class="form-control" />
        </div>
        <div class="form-group mb-2">
          <label>Number of Rows (Height)</label>
          <input v-model.number="height" type="text" class="form-control" />
        </div>
        <div class="form-group mb-2">
          <label>Tile Size (Width and Height)</label>
          <input v-model.number="tileSize" type="text" class="form-control" />
        </div>
      </div>
      <button @click="createMap" class="btn btn-primary mt-3">Create</button>
    </div>
  </div>
</template>
