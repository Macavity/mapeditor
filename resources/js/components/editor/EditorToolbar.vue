<script setup lang="ts">
import { useEditorStore } from '@/stores/editorStore';
import { EditorTool } from '@/types/EditorTool';
import { Eraser, FileJson, Grid, Layers, Paintbrush, PaintBucket } from 'lucide-vue-next';

const store = useEditorStore();
</script>

<template>
    <div class="flex items-center gap-4" role="toolbar">
        <!-- Import JSON Button -->
        <div class="flex" role="group">
            <button
                type="button"
                class="border-primary text-primary hover:bg-primary flex items-center gap-2 rounded-lg border px-4 py-2 transition-colors hover:text-white"
                data-toggle="modal"
                data-target="#import-json-modal"
            >
                <FileJson class="h-4 w-4" />
                Import JSON File
            </button>
        </div>

        <!-- Toggle Buttons -->
        <div class="flex gap-2" role="group">
            <button
                type="button"
                @click="store.toggleGrid()"
                class="flex items-center gap-2 rounded-lg border px-4 py-2 transition-colors"
                :class="[
                    store.showGrid
                        ? 'bg-secondary text-secondary-foreground border-secondary'
                        : 'border-secondary text-secondary hover:bg-secondary/10',
                ]"
            >
                <Grid class="h-4 w-4" />
                Show Grid
            </button>

            <button
                type="button"
                @click="store.toggleProperties()"
                class="flex items-center gap-2 rounded-lg border px-4 py-2 transition-colors"
                :class="[
                    store.showProperties
                        ? 'bg-secondary text-secondary-foreground border-secondary'
                        : 'border-secondary text-secondary hover:bg-secondary/10',
                ]"
            >
                <Layers class="h-4 w-4" />
                Show Properties
            </button>
        </div>

        <!-- Tools -->
        <div class="flex gap-2" role="group">
            <button
                @click="store.selectTool(EditorTool.DRAW)"
                title="Brush Tool (B)"
                class="flex items-center rounded-lg border px-4 py-2 transition-colors"
                :class="[
                    store.isDrawToolActive ? 'bg-primary text-primary-foreground border-primary' : 'border-primary text-primary hover:bg-primary/10',
                ]"
            >
                <Paintbrush class="h-4 w-4" />
            </button>

            <button
                @click="store.selectTool(EditorTool.FILL)"
                title="Bucket Fill Tool (F)"
                class="flex items-center rounded-lg border px-4 py-2 transition-colors"
                :class="[
                    store.isFillToolActive ? 'bg-primary text-primary-foreground border-primary' : 'border-primary text-primary hover:bg-primary/10',
                ]"
            >
                <PaintBucket class="h-4 w-4" />
            </button>

            <button
                @click="store.selectTool(EditorTool.ERASE)"
                title="Eraser (E)"
                class="flex items-center rounded-lg border px-4 py-2 transition-colors"
                :class="[
                    store.isEraseToolActive ? 'bg-primary text-primary-foreground border-primary' : 'border-primary text-primary hover:bg-primary/10',
                ]"
            >
                <Eraser class="h-4 w-4" />
            </button>
        </div>
    </div>
</template>

<style scoped></style>
