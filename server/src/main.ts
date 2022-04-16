import { Logger } from "@nestjs/common";
import { NestFactory } from "@nestjs/core";
import "reflect-metadata";
import { AppModule } from "./app.module";
import "./plugins/dotenv";
import { initializeSwagger } from "./plugins/swagger";

async function bootstrap() {
    const app = await NestFactory.create(AppModule);

    initializeSwagger(app);
    app.enableCors();

    await app.listen(8888);
    Logger.log(
        `Server started inside the contain on Port 8888.`,
        "Bootstrap",
    );
    Logger.log(
        `Exposed to the host the server is running on http://localhost:${process.env.APP_PORT}`,
        "Bootstrap",
    );
}

bootstrap();
