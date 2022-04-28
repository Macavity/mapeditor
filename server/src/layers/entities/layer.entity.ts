import {
    Column,
    CreateDateColumn,
    DeleteDateColumn,
    Entity,
    Generated,
    ManyToOne,
    PrimaryGeneratedColumn,
    UpdateDateColumn,
} from 'typeorm';
import { TileMap } from '../../tile-maps/entities/tile-map.entity';

@Entity()
export class Layer {
    @PrimaryGeneratedColumn()
    public id: number;

    @Column()
    @Generated('uuid')
    public uuid: string;

    @ManyToOne(() => TileMap, (map) => map.layers)
    public tileMap: TileMap;

    @Column()
    public name: string;

    @Column({ default: 0 })
    public x: number;

    @Column({ default: 0 })
    public y: number;

    @Column({ default: 0 })
    public z: number;

    @Column()
    public width: number;

    @Column()
    public height: number;

    @Column({ type: 'jsonb' })
    public data: LayerData;

    @Column({ default: true })
    public visible: boolean;

    @Column({ default: 1.0 })
    public opacity: number;

    @CreateDateColumn()
    createdDate: Date;

    @UpdateDateColumn()
    updatedDate: Date;

    @DeleteDateColumn()
    deletedDate: Date;
}
