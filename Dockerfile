#最小的alpine Linux(10Mb)
FROM alpine:latest

#安装init
RUN sed -i 's/dl-cdn.alpinelinux.org/mirrors.aliyun.com/g' /etc/apk/repositories \
   && apk add wget curl nload php7-cli php7-redis php7-pcntl php7-posix php7-iconv php7-pdo php7-gd php7-pdo_mysql php7-ctype php7-mbstring \
  && apk add composer libevent \
   && composer config -g repo.packagist composer https://mirrors.aliyun.com/composer/

WORKDIR /app
COPY / /app

RUN    composer install --ignore-platform-reqs \
        && mkdir -p /tmp/logs \
         && mkdir -p /tmp/sessions \
        && mkdir -p /tmp/views \
        && rm -rf /app/runtime \
        && ln -s /tmp  /app/runtime

#暴露8787端口
EXPOSE 8787

CMD ["php","/app/start.php","start"]