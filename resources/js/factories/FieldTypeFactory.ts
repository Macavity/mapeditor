import type { FieldTypeTile } from '@/types/MapLayer';

export interface FieldTypeData {
    x: number;
    y: number;
    fieldType: number;
}

export class FieldTypeFactory {
    /**
     * Create a valid field type object
     */
    static create(data: FieldTypeData): FieldTypeTile {
        // Validate input data
        if (typeof data.x !== 'number' || typeof data.y !== 'number') {
            throw new Error('Field type coordinates must be numbers');
        }

        if (typeof data.fieldType !== 'number' || data.fieldType < 0) {
            throw new Error('Field type ID must be a non-negative number');
        }

        return {
            x: data.x,
            y: data.y,
            fieldType: data.fieldType,
        };
    }

    /**
     * Create a field type object from raw data with validation
     */
    static fromRaw(data: any): FieldTypeTile | null {
        try {
            // Check if data has the required structure
            if (!data || typeof data !== 'object') {
                return null;
            }

            if (typeof data.x !== 'number' || typeof data.y !== 'number') {
                return null;
            }

            if (typeof data.fieldType !== 'number' || data.fieldType < 0) {
                return null;
            }

            return {
                x: data.x,
                y: data.y,
                fieldType: data.fieldType,
            };
        } catch (error) {
            console.warn('Failed to create field type from raw data:', error);
            return null;
        }
    }

    /**
     * Validate if an object is a valid field type
     */
    static isValid(fieldType: any): fieldType is FieldTypeTile {
        if (!fieldType || typeof fieldType !== 'object') {
            return false;
        }

        if (typeof fieldType.x !== 'number' || typeof fieldType.y !== 'number') {
            return false;
        }

        if (typeof fieldType.fieldType !== 'number' || fieldType.fieldType < 0) {
            return false;
        }

        return true;
    }

    /**
     * Create a field type at a specific position
     */
    static createAtPosition(x: number, y: number, fieldType: number): FieldTypeTile {
        return this.create({
            x,
            y,
            fieldType,
        });
    }
}
