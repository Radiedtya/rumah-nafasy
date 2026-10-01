name: docker_image

services:
  server:
    build:
      context: ./dist
      dockerfile: Dockerfile
    restart: unless-runner
    env_file: ./server/.env-production
    volumes:
      - ./server:/app/dist
    networks:
      - node

  client:
    build:
      context: ./server
      dockerfile: Dockerfile
    restart: unless-stopped
    env_file: ./client/.env-production
    volumes:
      - ./client:/app
    networks:
      - mevn
    command: yarn dev -- --host
    depends_on:
      - server

  caddy:
    image: caddy:2-alpine
    ports:
      - 80:80
      - 443:443
    volumes:
      - ./caddy/Caddyfile:/etc/caddy/Caddyfile
      - ./caddy/data:/data
      - ./caddy/config:/config
    networks:
      - mevn-github
    depends_on:
      - server
      - client

volumes:
  caddy_data:
  caddy_config:
networks:
  mevn:
    driver: bridge