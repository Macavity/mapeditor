import { api } from '@/lib/api';

export interface FieldType {
    id: number;
    name: string;
    color: string;
    created_at: string;
    updated_at: string;
}

export interface CreateFieldTypeData {
    name: string;
    color: string;
}

export interface UpdateFieldTypeData {
    name: string;
    color: string;
}

export class FieldTypeService {
    static async getAll(): Promise<FieldType[]> {
        const response = await api.get('/field-types');
        return response.data.data;
    }

    static async getById(id: number): Promise<FieldType> {
        const response = await api.get(`/field-types/${id}`);
        return response.data.data;
    }

    static async create(data: CreateFieldTypeData): Promise<FieldType> {
        const response = await api.post('/field-types', data);
        return response.data.data;
    }

    static async update(id: number, data: UpdateFieldTypeData): Promise<FieldType> {
        const response = await api.put(`/field-types/${id}`, data);
        return response.data.data;
    }

    static async delete(id: number): Promise<void> {
        await api.delete(`/field-types/${id}`);
    }
}
