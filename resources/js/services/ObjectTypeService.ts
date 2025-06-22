import { api } from '@/lib/api';

export interface ObjectType {
    id: number;
    name: string;
    type: string;
    color: string;
    description: string | null;
    is_solid: boolean;
    created_at: string;
    updated_at: string;
}

export interface CreateObjectTypeData {
    name: string;
    type?: string;
    color: string;
    description?: string;
    is_solid?: boolean;
}

export interface UpdateObjectTypeData {
    name: string;
    type?: string;
    color: string;
    description?: string;
    is_solid?: boolean;
}

export class ObjectTypeService {
    static async getAll(): Promise<ObjectType[]> {
        const response = await api.get('/object-types');
        return response.data.data;
    }

    static async getById(id: number): Promise<ObjectType> {
        const response = await api.get(`/object-types/${id}`);
        return response.data.data;
    }

    static async create(data: CreateObjectTypeData): Promise<ObjectType> {
        const response = await api.post('/object-types', data);
        return response.data.data;
    }

    static async update(id: number, data: UpdateObjectTypeData): Promise<ObjectType> {
        const response = await api.put(`/object-types/${id}`, data);
        return response.data.data;
    }

    static async delete(id: number): Promise<void> {
        await api.delete(`/object-types/${id}`);
    }
}
