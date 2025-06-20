import type { Tile } from '@/types/MapLayer';

export interface TileData {
    x: number;
    y: number;
    tileset: string;
    tileX: number;
    tileY: number;
}

export class TileFactory {
    /**
     * Create a valid tile object
     */
    static create(data: TileData): Tile {
        // Validate input data
        if (typeof data.x !== 'number' || typeof data.y !== 'number') {
            throw new Error('Tile coordinates must be numbers');
        }

        if (typeof data.tileset !== 'string' || data.tileset.trim() === '') {
            throw new Error('Tile tileset must be a non-empty string');
        }

        if (typeof data.tileX !== 'number' || typeof data.tileY !== 'number') {
            throw new Error('Tile tileX and tileY must be numbers');
        }

        return {
            x: data.x,
            y: data.y,
            brush: {
                tileset: data.tileset,
                tileX: data.tileX,
                tileY: data.tileY,
            },
        };
    }

    /**
     * Create a tile object from raw data with validation
     */
    static fromRaw(data: any): Tile | null {
        try {
            // Check if data has the required structure
            if (!data || typeof data !== 'object') {
                return null;
            }

            if (typeof data.x !== 'number' || typeof data.y !== 'number') {
                return null;
            }

            if (!data.brush || typeof data.brush !== 'object') {
                return null;
            }

            if (typeof data.brush.tileset !== 'string' || typeof data.brush.tileX !== 'number' || typeof data.brush.tileY !== 'number') {
                return null;
            }

            return {
                x: data.x,
                y: data.y,
                brush: {
                    tileset: data.brush.tileset,
                    tileX: data.brush.tileX,
                    tileY: data.brush.tileY,
                },
            };
        } catch (error) {
            console.warn('Failed to create tile from raw data:', error);
            return null;
        }
    }

    /**
     * Validate if an object is a valid tile
     */
    static isValid(tile: any): tile is Tile {
        if (!tile || typeof tile !== 'object') {
            return false;
        }

        if (typeof tile.x !== 'number' || typeof tile.y !== 'number') {
            return false;
        }

        if (!tile.brush || typeof tile.brush !== 'object') {
            return false;
        }

        if (typeof tile.brush.tileset !== 'string' || typeof tile.brush.tileX !== 'number' || typeof tile.brush.tileY !== 'number') {
            return false;
        }

        return true;
    }

    /**
     * Create a tile at a specific position with default brush values
     */
    static createAtPosition(x: number, y: number, tileset: string, tileX: number, tileY: number): Tile {
        return this.create({
            x,
            y,
            tileset,
            tileX,
            tileY,
        });
    }
}
