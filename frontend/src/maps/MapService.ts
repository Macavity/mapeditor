import type { CreateMapDto } from "@/maps/dtos/CreateMap.dto";
import { mande } from "mande";

const maps = mande("/api/maps/");

export function createMap(newMap: CreateMapDto): Promise<IMap> {
  return maps.post(newMap);
}

export function getMaps(): Promise<IMap[]> {
  return maps.get<IMap[]>();
}
