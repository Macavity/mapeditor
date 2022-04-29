import { getConnection, getConnectionOptions } from "TypeORM";
import { environmentName } from "../database/database.provider";

export const getDbConnectionOptions = async (connectionName: string = "default",
) => {
    const options = await getConnectionOptions(environmentName);
    return {
        ...options,
        name: connectionName,
    };
};

export const getDbConnection = async (connectionName: string = "default") => {
    return getConnection(connectionName);
};

export const runDbMigrations = async (connectionName: string = "default") => {
    const conn = await getDbConnection(connectionName);
    await conn.runMigrations();
};
