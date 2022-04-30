import { getConnection, getConnectionOptions } from 'TypeORM';
import { environmentName } from '../database/database.provider';

export const getDbConnectionOptions = async (connectionName = 'default') => {
  const options = await getConnectionOptions(environmentName);
  return {
    ...options,
    name: connectionName,
  };
};

export const getDbConnection = async (connectionName = 'default') => {
  return getConnection(connectionName);
};

export const runDbMigrations = async (connectionName = 'default') => {
  const conn = await getDbConnection(connectionName);
  await conn.runMigrations();
};
