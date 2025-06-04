<script setup lang="ts">
import { useEditorStore } from '@/stores/editorStore';
import { EditorTool } from '@/types/EditorTool';

const store = useEditorStore();
</script>

<template>
    <div class="flex items-center gap-4" role="toolbar">
        <!-- Import JSON Button -->
        <div class="flex" role="group">
            <button
                type="button"
                class="border-primary text-primary hover:bg-primary rounded-lg border px-4 py-2 transition-colors hover:text-white"
                data-toggle="modal"
                data-target="#import-json-modal"
            >
                Import JSON File
            </button>
        </div>

        <!-- Toggle Buttons -->
        <div class="flex gap-2" role="group">
            <button
                type="button"
                @click="store.toggleGrid()"
                class="flex items-center gap-2 rounded-lg border px-4 py-2 transition-colors"
                :class="[store.showGrid ? 'bg-secondary border-secondary text-white' : 'border-secondary text-secondary hover:bg-secondary/10']"
            >
                <i class="bi" :class="{ 'bi-check-square': store.showGrid, 'bi-square': !store.showGrid }"></i>
                Show Grid
            </button>

            <button
                type="button"
                @click="store.toggleProperties()"
                class="flex items-center gap-2 rounded-lg border px-4 py-2 transition-colors"
                :class="[store.showProperties ? 'bg-secondary border-secondary text-white' : 'border-secondary text-secondary hover:bg-secondary/10']"
            >
                <i class="bi" :class="{ 'bi-check-square': store.showProperties, 'bi-square': !store.showProperties }"></i>
                Show Properties
            </button>
        </div>

        <!-- Tools -->
        <div class="flex gap-2" role="group">
            <button
                @click="store.selectTool(EditorTool.DRAW)"
                title="Brush Tool (B)"
                class="flex items-center rounded-lg border px-4 py-2 transition-colors"
                :class="[store.isDrawToolActive ? 'bg-primary border-primary text-white' : 'border-primary text-primary hover:bg-primary/10']"
            >
                <span class="bi bi-pencil"></span>
            </button>

            <button
                @click="store.selectTool(EditorTool.FILL)"
                title="Bucket Fill Tool (F)"
                class="flex items-center rounded-lg border px-4 py-2 transition-colors"
                :class="[store.isFillToolActive ? 'bg-primary border-primary text-white' : 'border-primary text-primary hover:bg-primary/10']"
            >
                <span class="bi bi-bucket"></span>
            </button>

            <button
                @click="store.selectTool(EditorTool.ERASE)"
                title="Eraser (E)"
                class="flex items-center rounded-lg border px-4 py-2 transition-colors"
                :class="[store.isEraseToolActive ? 'bg-primary border-primary text-white' : 'border-primary text-primary hover:bg-primary/10']"
            >
                <span class="bi bi-eraser"></span>
            </button>
        </div>
    </div>
</template>

<style scoped></style>
