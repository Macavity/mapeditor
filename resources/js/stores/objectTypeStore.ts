import type { ObjectType } from '@/services/ObjectTypeService';
import axios from 'axios';
import { defineStore } from 'pinia';

interface ObjectTypeState {
    activeObjectTypeId: number | null;
    objectTypes: ObjectType[];
    loading: boolean;
    error: string | null;
}

export const useObjectTypeStore = defineStore('objectTypeStore', {
    persist: true,
    state: (): ObjectTypeState => ({
        activeObjectTypeId: null,
        objectTypes: [],
        loading: false,
        error: null,
    }),
    getters: {
        activeObjectType: (state): ObjectType | undefined => {
            return state.objectTypes.find((objectType) => objectType.id === state.activeObjectTypeId);
        },
    },
    actions: {
        async initialize() {
            // If we have a persisted activeObjectTypeId but no objectTypes loaded, load them
            if (this.activeObjectTypeId && this.objectTypes.length === 0 && !this.loading) {
                await this.loadObjectTypes();
            }
        },
        activateObjectType(id: number) {
            this.activeObjectTypeId = id;
        },
        addObjectType(objectType: ObjectType) {
            this.objectTypes.push(objectType);
        },
        async loadObjectTypes() {
            this.loading = true;
            this.error = null;
            try {
                const response = await axios.get('/api/object-types');
                this.objectTypes = response.data.data;

                // Check if the current activeObjectTypeId is still valid
                const currentActiveExists =
                    this.activeObjectTypeId && this.objectTypes.find((objectType) => objectType.id === this.activeObjectTypeId);

                // Set the first object type as active if none is selected or if current active is invalid
                if (!currentActiveExists && this.objectTypes.length > 0) {
                    this.activeObjectTypeId = this.objectTypes[0].id;
                }
            } catch (error) {
                this.error = 'Failed to load object types';
                throw error;
            } finally {
                this.loading = false;
            }
        },
        async deleteObjectType(id: number) {
            try {
                await axios.delete(`/api/object-types/${id}`);
                this.objectTypes = this.objectTypes.filter((objectType) => objectType.id !== id);

                // If the deleted object type was active, switch to another one
                if (this.activeObjectTypeId === id) {
                    this.activeObjectTypeId = this.objectTypes.length > 0 ? this.objectTypes[0].id : null;
                }
            } catch (error) {
                this.error = 'Failed to delete object type';
                throw error;
            }
        },
    },
});
