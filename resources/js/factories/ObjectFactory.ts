import type { ObjectTile } from '@/types/MapLayer';

export interface ObjectData {
    x: number;
    y: number;
    objectType: number;
}

export class ObjectFactory {
    /**
     * Create a valid object tile
     */
    static create(data: ObjectData): ObjectTile {
        // Validate input data
        if (typeof data.x !== 'number' || typeof data.y !== 'number') {
            throw new Error('Object coordinates must be numbers');
        }

        if (typeof data.objectType !== 'number' || data.objectType < 0) {
            throw new Error('Object type ID must be a non-negative number');
        }

        return {
            x: data.x,
            y: data.y,
            objectType: data.objectType,
        };
    }

    /**
     * Create an object tile from raw data with validation
     */
    static fromRaw(data: any): ObjectTile | null {
        try {
            // Check if data has the required structure
            if (!data || typeof data !== 'object') {
                return null;
            }

            if (typeof data.x !== 'number' || typeof data.y !== 'number') {
                return null;
            }

            if (typeof data.objectType !== 'number' || data.objectType < 0) {
                return null;
            }

            return {
                x: data.x,
                y: data.y,
                objectType: data.objectType,
            };
        } catch (error) {
            console.warn('Failed to create object from raw data:', error);
            return null;
        }
    }

    /**
     * Validate if an object is a valid object tile
     */
    static isValid(object: any): object is ObjectTile {
        if (!object || typeof object !== 'object') {
            return false;
        }

        if (typeof object.x !== 'number' || typeof object.y !== 'number') {
            return false;
        }

        if (typeof object.objectType !== 'number' || object.objectType < 0) {
            return false;
        }

        return true;
    }

    /**
     * Create an object at a specific position
     */
    static createAtPosition(x: number, y: number, objectType: number): ObjectTile {
        return this.create({
            x,
            y,
            objectType,
        });
    }
}
