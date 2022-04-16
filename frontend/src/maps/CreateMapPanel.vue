<script setup lang="ts">
  import { CreateMapDto } from "@/maps/dtos/CreateMap.dto";
  import { ref } from "vue";

  const name = ref("");
  const width = ref(20);
  const height = ref(20);

  function createMap() {
    const newMap = new CreateMapDto(name.value, width.value, height.value);
    console.log(newMap);

    //
    //
    // Meteor.call('createMap', newMap, function(error, result){
    //   if(!!error && Meteor.isClient){
    //     sAlert.error(error);
    //   }
    //   else {
    //     Router.go('/map/edit/'+result);
    //   }
    // });
  }
</script>

<template>
  <div class="panel panel-default">
    <div class="panel-heading">
      <h3 class="panel-title">Create new Map</h3>
    </div>
    <div class="panel-body">
      <form>
        <div class="form-group">
          <label>Name</label>
          <input v-model="name" type="text" class="form-control" />
        </div>
        <div class="form-group">
          <label>Fields per Row (Width)</label>
          <input v-model.number="width" type="text" class="form-control" />
        </div>
        <div class="form-group">
          <label>Number of Rows (Height)</label>
          <input v-model.number="height" type="text" class="form-control" />
        </div>
      </form>
    </div>
    <div class="panel-footer">
      <button @click="createMap" class="btn btn-primary">Create</button>
    </div>
  </div>
</template>
