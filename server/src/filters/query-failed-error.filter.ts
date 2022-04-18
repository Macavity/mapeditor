import { ArgumentsHost, Catch, ExceptionFilter, HttpStatus, Logger } from "@nestjs/common";
import { Request, Response } from "express";
import { QueryFailedError } from "typeorm";

@Catch(QueryFailedError)
export class QueryFailedExceptionFilter implements ExceptionFilter {
    catch(exception: QueryFailedError, host: ArgumentsHost) {
        const logger = new Logger(QueryFailedExceptionFilter.name);
        const context = host.switchToHttp();
        const response = context.getResponse<Response>();
        const request = context.getRequest<Request>();
        const { url } = request;

        let message = exception.message;

        // if (exception.message.indexOf('invalid input syntax for type integer')) {
        //     message = 'Invalid number in field '
        // }

        const errorResponse = {
            statusCode: HttpStatus.BAD_REQUEST,
            path: url,
            timestamp: new Date().toISOString(),
            error: exception.name,
            message: exception.message,
        };
        logger.warn(exception);

        response
            .status(HttpStatus.BAD_REQUEST)
            .json(errorResponse);
    }
}
