# Servidor PHP + Apache para o portfólio (as páginas HTML continuam iguais;
# os projetos com index.php passam a ser executados pelo PHP).
FROM php:8.3-apache

RUN a2enmod headers \
 && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Sem listagem de pastas, sem versão do servidor à vista e com cabeçalhos básicos de segurança
RUN printf '%s\n' \
    'ServerTokens Prod' \
    'ServerSignature Off' \
    'Header always set X-Content-Type-Options "nosniff"' \
    'Header always set Referrer-Policy "strict-origin-when-cross-origin"' \
    '<Directory /var/www/html>' \
    '    Options -Indexes' \
    '</Directory>' \
    '<FilesMatch "^(Dockerfile|render\.yaml|\.dockerignore|.*\.ps1)$">' \
    '    Require all denied' \
    '</FilesMatch>' \
    > /etc/apache2/conf-available/portfolio.conf \
 && a2enconf portfolio

COPY . /var/www/html/

# O Render indica a porta na variável PORT (por omissão 10000)
ENV PORT=10000
EXPOSE 10000
CMD ["sh", "-c", "sed -i \"s/Listen 80/Listen ${PORT}/\" /etc/apache2/ports.conf && sed -i \"s/<VirtualHost \\*:80>/<VirtualHost *:${PORT}>/\" /etc/apache2/sites-available/000-default.conf && exec apache2-foreground"]
