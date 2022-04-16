FROM node:lts-alpine

WORKDIR /server

COPY . .

RUN ["npm", "install", "-g", "nodemon", "--silent"]
RUN ["npm", "install", "-g", "@nestjs/cli"]
RUN ["apk", "add", "--no-cache", "bash"]

RUN ["npm", "install"]

EXPOSE 8888
