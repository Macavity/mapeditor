FROM node:lts-alpine

WORKDIR /frontend

COPY . .

RUN npm install

CMD ["npm", "run", "start:docker"]

EXPOSE 3000
