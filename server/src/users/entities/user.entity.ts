import {
  Column,
  CreateDateColumn,
  Entity,
  Generated,
  OneToMany,
  PrimaryGeneratedColumn,
  UpdateDateColumn,
} from 'typeorm';
import { TileMap } from '../../tile-maps/entities/tile-map.entity';

@Entity()
export class User {
  @PrimaryGeneratedColumn()
  public id: number;

  @Generated('uuid')
  public uuid: string;

  @Column({ length: 200 })
  public name: string;

  @OneToMany(() => TileMap, (map) => map.creator)
  public tileMaps: TileMap[];

  @CreateDateColumn()
  createdDate: Date;

  @UpdateDateColumn()
  updatedDate: Date;
}
