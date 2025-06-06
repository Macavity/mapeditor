# TileMap Editor

The editor gives you the ability to import json files that were saved in [Tiled](https://github.com/bjorn/tiled).

Keep in mind that this editor is still in a very early stage and not working yet.

![Screenshot](http://i.imgur.com/qwZRKFz.jpg?raw)

## How to use

### Example File

```json
{
    "height": 10,
    "layers": [
        {
            "data": [
                1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1,
                1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1,
                1, 1, 1, 1, 1, 1, 1, 1, 1, 1
            ],
            "height": 10,
            "name": "Background",
            "opacity": 1,
            "type": "tilelayer",
            "visible": true,
            "width": 10,
            "x": 0,
            "y": 0
        },
        {
            "data": [
                0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 41, 42, 43, 44, 0, 0, 0, 0, 0, 0, 49, 50, 51, 52, 0, 0, 0, 0, 0,
                0, 57, 58, 59, 60, 0, 0, 0, 0, 0, 0, 65, 66, 67, 68, 0, 0, 0, 0, 0, 0, 73, 74, 75, 76, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0
            ],
            "height": 10,
            "name": "Floor 1",
            "opacity": 1,
            "type": "tilelayer",
            "visible": true,
            "width": 10,
            "x": 0,
            "y": 0
        }
    ],
    "nextobjectid": 1,
    "orientation": "orthogonal",
    "properties": {},
    "renderorder": "right-down",
    "tileheight": 32,
    "tilesets": [
        {
            "firstgid": 1,
            "image": "..\/mapeditor_php\/export\/static\/images\/tilesets\/001-Grassland01.png",
            "imageheight": 576,
            "imagewidth": 256,
            "margin": 0,
            "name": "001-Grassland01",
            "properties": {},
            "spacing": 0,
            "tilecount": 144,
            "tileheight": 32,
            "tilewidth": 32
        }
    ],
    "tilewidth": 32,
    "version": 1,
    "width": 10
}
```

## Installation

```bash
$ npm install
```

## Running the app with Docker

```bash
docker-compose up -d
```

## Running the app without Docker

- Have Composer, PHP 8.2 and MySQL installed localled

```bash
composer dev
```

# Features

- Placing single tiles with the "Draw Tool"
- Erase tiles with the "Erase Tool"
- Open files from Tiled in json format

# To Do

Open to suggestions :)

- Actually save the Map (..yes yes, i know..)
- Import JSON Files
- Import TMX Files
- Minimap for larger maps
- Bucket Tool to fill larger areas
- Keyboard shortcuts for the tools
- Testdrive a map - walk around with a character, test collisions
- Support [TMX](https://github.com/bjorn/tiled/wiki/TMX-Map-Format) format as well

# Credits

Based on an old tile based browsergame for [Last Anixile](http://www.last-anixile.de)
The working original was written in PHP and rests in
the [legacy branch](https://github.com/Macavity/mapeditor/tree/legacy). Bear in mind that the code was created in 2005
;-) - 'twas a different time then.. I made just rough corrections so it works outside it's previous frame.

- Tileset magecity was made by [Hyptosis on opengameart](http://opengameart.org/content/mage-city-arcanos)
