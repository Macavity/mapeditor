<script setup lang="ts">
  import { EditorTool } from '@/editor/EditorTool';
  import { useEditorStore } from '@/editor/EditorStore';

  const store = useEditorStore();
</script>

<template>
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
</template>

<style scoped></style>
