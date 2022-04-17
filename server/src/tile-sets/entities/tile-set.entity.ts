import {
    Column,
    CreateDateColumn,
    DeleteDateColumn,
    Entity,
    Generated,
    PrimaryGeneratedColumn,
    UpdateDateColumn,
} from "typeorm";

@Entity()
export class TileSet {
    @PrimaryGeneratedColumn()
    public id: number;

    @Generated("uuid")
    public uuid: string;

    @Column()
    public imageWidth: number;

    @Column()
    public imageHeight: number;

    @Column()
    public name: string;

    @Column()
    public tileWidth: number;

    @Column()
    public tileHeight: number;

    @Column()
    public tileCount: number;

    @Column()
    public firstGid: number;

    @Column({ default: 0 })
    public margin: number;

    @Column({ default: 0 })
    public spacing: number;

    @CreateDateColumn()
    createdDate: Date;

    @UpdateDateColumn()
    updatedDate: Date;

    @DeleteDateColumn()
    deletedDate: Date;
}
