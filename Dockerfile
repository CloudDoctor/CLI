FROM alpine:3.8
ADD start_runit /sbin/
RUN mkdir /etc/container_environment && \
    mkdir /cache && \
    chmod 777 /cache && \
    chmod a+x /sbin/start_runit && \
    mkdir /etc/service && \
    mkdir /etc/runit_init.d && \
    apk add --update \
    runit \
    php7 \
    php7-common \
    php7-openssl \
    php7-tokenizer \
    php7-mbstring \
    php7-simplexml \
    php7-json \
    php7-zip \
    php7-curl \
    php7-posix \
    bash \
    ncurses \
    coreutils \
    && \
    rm -rf /var/cache/apk/*

WORKDIR /app

# Copy cron & worker tasks into location and chmod accordingly.
ADD ./ /app/

ENTRYPOINT ["/app/cloud-doctor"]
