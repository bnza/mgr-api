FROM redis:7.4-alpine

RUN mkdir -p /tmp/socket/redis
RUN chmod 777 /tmp/socket/redis
COPY ./docker/redis/redis.conf /etc/redis.conf

ENTRYPOINT ["docker-entrypoint.sh", "/etc/redis.conf"]

