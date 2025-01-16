export class UpdateTileMapDto {
    constructor(
        public name?: string,
        public width?: number,
        public height?: number,
        public tileWidth?: number,
        public tileHeight?: number
    ) {}
}