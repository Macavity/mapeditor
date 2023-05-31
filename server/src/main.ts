import {Logger} from "@nestjs/common";
import {NestFactory} from "@nestjs/core/nest-factory";
import 'reflect-metadata';
import {AppModule} from './app.module';
import './plugins/dotenv';
import {initializeSwagger} from './plugins/swagger';

async function bootstrap() {
  const app = await NestFactory.create(AppModule);

  await initializeSwagger(app);
  // await runDbMigrations();
  app.enableCors();

  await app.listen(8888, '0.0.0.0');
  Logger.log(`Server started inside the container on Port 8888 (0.0.0.0).`, 'Bootstrap');
  Logger.log(
    `Exposed to the host the server is running on http://localhost:${process.env.APP_PORT}`,
    'Bootstrap',
  );
}

bootstrap();
