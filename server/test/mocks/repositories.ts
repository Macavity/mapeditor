import { getRepositoryToken } from '@nestjs/typeorm';

const mock = () => ({
  metadata: {
    connection: { options: { type: null } },
    columns: [],
    relations: [],
  },
});

export const mockRepositoryProvider = (type) => {
  return {
    provide: getRepositoryToken(type),
    useFactory: mock,
  };
};
