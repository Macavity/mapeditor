import { Exclude } from 'class-transformer';
import { IsInt, IsNotEmpty } from 'class-validator';
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
    @Exclude()
    public id: number;

    @Column()
    @Generated('uuid')
    public uuid: string;

    @ManyToOne(() => User, (user) => user.tileMaps)
    public creator: User;

    @Column()
    @IsInt({ message: 'Width must be a number.' })
    public width: number;

    @Column()
    @IsInt({ message: 'Height must be a number.' })
    public height: number;

    @Column()
    @IsNotEmpty({ message: 'Name may not be empty.' })
    public name: string;

    @Column()
    @IsInt({ message: 'Tile Width must be a number.' })
    public tileWidth: number;

    @Column()
    @IsInt({ message: 'Tile Height must be a number.' })
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
