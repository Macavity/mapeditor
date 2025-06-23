<script setup lang="ts">
import { ToggleButton } from '@/components/ui/button';
import { useEditorStore } from '@/stores/editorStore';
import { EditorTool } from '@/types/EditorTool';
import { Eraser, Grid, Paintbrush, PaintBucket } from 'lucide-vue-next';

const store = useEditorStore();
</script>

<template>
    <div class="flex items-center gap-4" role="toolbar">
        <!-- Toggle Buttons -->
        <div class="flex gap-2" role="group">
            <ToggleButton :icon="Grid" text="Show Grid" :active="store.showGrid" @click="store.toggleGrid()" />
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
