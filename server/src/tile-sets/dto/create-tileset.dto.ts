import {IsNotEmpty, IsNumber, IsString} from 'class-validator';

export class CreateTileSetDto {
    @IsString()
    @IsNotEmpty()
    name: string;

    @IsString()
    @IsNotEmpty()
    image: string;

    @IsNumber()
    firstGid: number;

    @IsNumber()
    tileCount: number;

    @IsNumber()
    tileHeight: number;

    @IsNumber()
    tileWidth: number;

    @IsNumber()
    imageHeight: number;

    @IsNumber()
    imageWidth: number;

    @IsNumber()
    margin: number;

    @IsNumber()
    spacing: number;
}