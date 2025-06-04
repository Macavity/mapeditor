export class MapDto {
    constructor(
        public uuid: string,
        public name: string,

        public height: number,
        public width: number,
        public tileHeight: number,
        public tileWidth: number,
    ) {}
}
