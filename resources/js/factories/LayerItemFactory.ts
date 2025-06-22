import type { FieldTypeTile, ObjectTile, Tile } from '@/types/MapLayer';
import { FieldTypeFactory, type FieldTypeData } from './FieldTypeFactory';
import { ObjectFactory, type ObjectData } from './ObjectFactory';
import { TileFactory, type TileData } from './TileFactory';

export type LayerItemData = TileData | FieldTypeData | ObjectData;

export class LayerItemFactory {
    /**
     * Create a tile, field type, or object based on the data structure
     */
    static create(data: LayerItemData): Tile | FieldTypeTile | ObjectTile {
        if ('fieldType' in data) {
            return FieldTypeFactory.create(data as FieldTypeData);
        } else if ('tileset' in data) {
            return TileFactory.create(data as TileData);
        } else if ('objectType' in data) {
            return ObjectFactory.create(data as ObjectData);
        } else {
            throw new Error('Invalid layer item data: must have either fieldType, tileset, or objectType property');
        }
    }

    /**
     * Create an object from raw data with automatic type detection
     */
    static fromRaw(data: any): Tile | FieldTypeTile | ObjectTile | null {
        if (!data || typeof data !== 'object') {
            return null;
        }

        // Try to create as field type first
        if ('fieldType' in data) {
            return FieldTypeFactory.fromRaw(data);
        }

        // Try to create as tile
        if ('brush' in data) {
            return TileFactory.fromRaw(data);
        }

        // Try to create as object
        if ('objectType' in data) {
            return ObjectFactory.fromRaw(data);
        }

        return null;
    }

    /**
     * Validate if an object is a valid layer item (tile, field type, or object)
     */
    static isValid(item: any): item is Tile | FieldTypeTile | ObjectTile {
        return TileFactory.isValid(item) || FieldTypeFactory.isValid(item) || ObjectFactory.isValid(item);
    }

    /**
     * Get the type of a layer item
     */
    static getType(item: Tile | FieldTypeTile | ObjectTile): 'tile' | 'fieldType' | 'object' {
        if ('brush' in item) {
            return 'tile';
        } else if ('fieldType' in item) {
            return 'fieldType';
        } else if ('objectType' in item) {
            return 'object';
        } else {
            throw new Error('Unknown layer item type');
        }
    }

    /**
     * Filter and validate an array of raw layer items
     */
    static filterValidItems(items: any[]): (Tile | FieldTypeTile | ObjectTile)[] {
        return items.map((item) => this.fromRaw(item)).filter((item): item is Tile | FieldTypeTile | ObjectTile => item !== null);
    }

    /**
     * Create a tile at a specific position
     */
    static createTileAtPosition(x: number, y: number, tileset: string, tileX: number, tileY: number): Tile {
        return TileFactory.createAtPosition(x, y, tileset, tileX, tileY);
    }

    /**
     * Create a field type at a specific position
     */
    static createFieldTypeAtPosition(x: number, y: number, fieldTypeId: number): FieldTypeTile {
        return FieldTypeFactory.createAtPosition(x, y, fieldTypeId);
    }

    /**
     * Create an object at a specific position
     */
    static createObjectAtPosition(x: number, y: number, objectTypeId: number): ObjectTile {
        return ObjectFactory.createAtPosition(x, y, objectTypeId);
    }

    /**
     * Check if an object is a tile
     */
    static isTile(item: any): item is Tile {
        return TileFactory.isValid(item);
    }

    /**
     * Check if an object is a field type
     */
    static isFieldType(item: any): item is FieldTypeTile {
        return FieldTypeFactory.isValid(item);
    }

    /**
     * Check if an object is an object tile
     */
    static isObject(item: any): item is ObjectTile {
        return ObjectFactory.isValid(item);
    }
}
