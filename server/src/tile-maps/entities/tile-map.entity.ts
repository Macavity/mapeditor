import {
  Column,
  CreateDateColumn,
  DeleteDateColumn,
  Entity,
  Generated,
  ManyToOne,
  OneToMany,
  PrimaryGeneratedColumn,
  UpdateDateColumn,
} from 'typeorm';
import { User } from '../../users/entities/user.entity';
import { Layer } from './layer.entity';

@Entity()
export class TileMap {
  @PrimaryGeneratedColumn()
  public id: number;

  @Generated('uuid')
  public uuid: string;

  @ManyToOne(() => User, (user) => user.tileMaps)
  public creator: User;

  @Column()
  public width: number;

  @Column()
  public height: number;

  @Column()
  public name: string;

  @Column()
  public tileWidth: number;

  @Column()
  public tileHeight: number;

  @OneToMany(() => Layer, (layer) => layer.tileMap)
  public layers: Layer[];

  @CreateDateColumn()
  createdDate: Date;

  @UpdateDateColumn()
  updatedDate: Date;

  @DeleteDateColumn()
  deletedDate: Date;
}
