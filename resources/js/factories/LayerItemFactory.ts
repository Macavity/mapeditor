import type { FieldTypeTile, Tile } from '@/types/MapLayer';
import { FieldTypeFactory, type FieldTypeData } from './FieldTypeFactory';
import { TileFactory, type TileData } from './TileFactory';

export type LayerItemData = TileData | FieldTypeData;

export class LayerItemFactory {
    /**
     * Create a tile or field type object based on the data structure
     */
    static create(data: LayerItemData): Tile | FieldTypeTile {
        if ('fieldType' in data) {
            return FieldTypeFactory.create(data as FieldTypeData);
        } else if ('tileset' in data) {
            return TileFactory.create(data as TileData);
        } else {
            throw new Error('Invalid layer item data: must have either fieldType or tileset property');
        }
    }

    /**
     * Create an object from raw data with automatic type detection
     */
    static fromRaw(data: any): Tile | FieldTypeTile | null {
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

        return null;
    }

    /**
     * Validate if an object is a valid layer item (tile or field type)
     */
    static isValid(item: any): item is Tile | FieldTypeTile {
        return TileFactory.isValid(item) || FieldTypeFactory.isValid(item);
    }

    /**
     * Get the type of a layer item
     */
    static getType(item: Tile | FieldTypeTile): 'tile' | 'fieldType' {
        if ('brush' in item) {
            return 'tile';
        } else if ('fieldType' in item) {
            return 'fieldType';
        } else {
            throw new Error('Unknown layer item type');
        }
    }

    /**
     * Filter and validate an array of raw layer items
     */
    static filterValidItems(items: any[]): (Tile | FieldTypeTile)[] {
        return items.map((item) => this.fromRaw(item)).filter((item): item is Tile | FieldTypeTile => item !== null);
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
}
