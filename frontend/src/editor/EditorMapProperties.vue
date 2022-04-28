<template>
  <div class="editor-properties">
    <div class="panel-heading">
      <h2 class="panel-title">Properties</h2>
    </div>
    <div class="panel-body">
      <form class="form-inline form-properties">
        <div class="form-group" v-for="(prop, i) in properties" :key="i">
          <label>{{ prop.field }}</label>
          <input
            type="text"
            :value="prop.value"
            class="form-control map-property"
          />
        </div>
      </form>
      <div class="btn-group">
        <button>+</button>
        <button>-</button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import type { MapDto } from './Map.dto';

  const props = defineProps<{
    map: MapDto;
  }>();

  const properties = [];

  // for (var key in props.map.properties) {
  //   if (map.properties.hasOwnProperty(key)) {
  //     properties.push({
  //       field: key, value: map.properties[key]
  //     });
  //   }
  // }
  // properties.push({
  //   field: "Author", value: props.map.creatorName, protected: true
  // });
  properties.push({
    field: 'Name',
    value: props.map.name,
  });
  properties.push({
    field: 'Width',
    value: props.map.width,
  });
  properties.push({
    field: 'Height',
    value: props.map.height,
  });
  properties.push({
    field: 'Tile Height',
    value: props.map.tileHeight,
  });
  properties.push({
    field: 'Tile Width',
    value: props.map.tileWidth,
  });
</script>

<style scoped lang="scss">
  .form-properties {
    label {
      width: 100px;
    }
  }
  .panel-body {
    padding: 10px;
  }
</style>
