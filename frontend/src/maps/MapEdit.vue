<template>
  <div class="map-edit">
    <h1>Map: {{ map.name }}</h1>

    <div class="row m-b-2">
      <div class="col-md-12">
        <div class="btn-toolbar mb-3" role="toolbar">
          <div class="btn-group me-2" role="group">
            <button
              type="button"
              class="btn btn-outline-primary"
              data-toggle="modal"
              data-target="#import-json-modal"
            >
              Import JSON File
            </button>
          </div>

          <div class="btn-group me-2" role="group">
            <button
              id="btn-show-grid"
              type="button"
              @click="store.toggleGrid()"
              class="btn"
              :class="{
                'btn-secondary': store.showGrid,
                'btn-outline-secondary': !store.showGrid,
              }"
            >
              <i
                class="bi"
                :class="{
                  'bi-check-square': store.showGrid,
                  'bi-square': !store.showGrid,
                }"
              ></i>
              Show Grid
            </button>
            <button
              id="btn-show-properties"
              type="button"
              @click="store.toggleProperties()"
              :class="{
                'btn-secondary': store.showProperties,
                'btn-outline-secondary': !store.showProperties,
              }"
              class="btn"
            >
              <i
                class="bi"
                :class="{
                  'bi-check-square': store.showProperties,
                  'bi-square': !store.showProperties,
                }"
              ></i>
              Show Properties
            </button>
          </div>

          <div id="toolkit" class="btn-group me-3" role="group">
            <button
              @click="store.selectTool(EditorTool.DRAW)"
              title="Brush Tool (B)"
              class="btn"
              :class="{
                'btn-primary': store.isDrawToolActive,
                'btn-outline-primary': !store.isDrawToolActive,
              }"
            >
              <span class="bi bi-pencil"></span>
            </button>
            <button
              @click="store.selectTool(EditorTool.FILL)"
              title="Bucket Fill Tool (F)"
              class="btn"
              :class="{
                'btn-primary': store.isFillToolActive,
                'btn-outline-primary': !store.isFillToolActive,
              }"
            >
              <span class="bi bi-bucket"></span>
            </button>
            <button
              @click="store.selectTool(EditorTool.ERASE)"
              title="Eraser (E)"
              class="btn"
              :class="{
                'btn-primary': store.isEraseToolActive,
                'btn-outline-primary': !store.isEraseToolActive,
              }"
            >
              <span class="bi bi-eraser"></span>
            </button>
          </div>
        </div>
      </div>
    </div>
    <div class="row">
      <aside
        id="aside-left"
        class="col-md-3"
        :class="{ hidden: store.showLeftSideBar }"
      >
        <section
          id="section-properties"
          class="panel panel-primary"
          :class="{ hidden: !showProperties }"
        >
          <!--          {{ >properties properties = mapProperties }}-->
        </section>
      </aside>

      <section
        id="section-canvas"
        :class="{
          'col-md-6': store.showLeftSideBar,
          'col-md-9': !store.showLeftSideBar,
        }"
      >
        <!--        {{ >canvas map = map layers = mapLayers canvasWidth = canvasWidth canvasHeight = canvasHeight }}-->
      </section>

      <aside id="aside-right" class="col-md-3">
        <!--section id="section-minimap" class="panel panel-primary">
            >minimap layers=mapLayers
        </section-->
        <section id="section-layers" class="panel panel-primary">
          <!-- layers layers = mapLayers-->
        </section>
        <section id="section-tilesets" class="panel panel-primary">
          <TileSetBox />
        </section>
      </aside>
    </div>
  </div>
</template>

<script setup lang="ts">
  import type { MapDto } from '@/maps/dtos/Map.dto';
  import { reactive } from 'vue';
  import { useMapStore } from '@/maps/MapStore';
  import TileSetBox from '@/maps/TileSetBox.vue';
  import { EditorTool } from '@/maps/EditorTool';

  const props = defineProps<{
    map: MapDto;
  }>();
  const store = useMapStore();

  const map = reactive(props.map);
  const showGrid = store.showGrid;
  const showProperties = false;
</script>
