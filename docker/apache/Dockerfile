FROM httpd:2.4-alpine

RUN sed -i \
        -e 's/^#\(LoadModule proxy_module\)/\1/' \
        -e 's/^#\(LoadModule proxy_fcgi_module\)/\1/' \
        -e 's/^#\(LoadModule rewrite_module\)/\1/' \
        conf/httpd.conf

COPY vhost.conf /usr/local/apache2/conf/extra/vhost.conf

RUN echo 'Include conf/extra/vhost.conf' >> conf/httpd.conf

WORKDIR /var/www/html
