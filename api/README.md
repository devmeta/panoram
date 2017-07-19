# Verusados API
####
![Verusados](https://bytebucket.org/franciscomarasco/ver-usados-app/raw/a677f6a0a6d085eb1e2e8a382d5dc4a1befe4e65/public/images/iso-verusados-big.png?token=dcf3c3eeba60d50d3d365f5a247b7d1bd8f36408)

[![Latest Version](https://img.shields.io/packagist/v/tuupola/slim-skeleton.svg?style=flat-square)](https://github.com/tuupola/slim-skeleton/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

This is Slim 3 API skeleton project for Composer. Project uses [Spot](http://phpdatamapper.com/) as persistence layer,  [Monolog](https://github.com/Seldaek/monolog) for logging, and [Fractal](http://fractal.thephpleague.com/) as serializer. [Vagrant](https://www.vagrantup.com/) virtualmachine config and [Paw](https://geo.itunes.apple.com/us/app/paw-http-rest-client/id584653203?mt=12&at=1010lc2t) project files are included for easy development.

## Install

Install [composer](https://getcomposer.org/) or type

``` bash
$ curl -sS https://getcomposer.org/installer | php
$ mv composer.phar /usr/local/bin/composer
```

## Usage

If you have [Vagrant](https://www.vagrantup.com/) installed start the virtual machine.

``` bash
$ cd app
$ vagrant up
```

### Configure your environment by creating .env file like this

```
APP_TITLE=Verusados
APP_URL=http://verusados.com
APP_REDIRECT_AFTER_LOGIN=/perfil-usuario/autos
APP_AD_DUE_DAYS=65
APP_LISTING_PER_PAGE=20
APP_IMAGE_UPLOAD_MAX=5000
APP_HASH_SALT=********
APP_JWT_SECRET=********
APP_JWT_EXPIRATION="30 minutes"
DB_NAME=verusados
DB_USER=root
DB_PASSWORD=********
DB_HOST=localhost
FB_APP_ID=********
FB_APP_SECRET=********
S3_KEY=********
S3_SECRET=********
S3_BUCKET=ver-usados
S3_REGION=us-east-1
S3_RESOLUTIONS=500x375,800x600,1080x810
MAIL_FROM=email@gmail.com
MAIL_PASSWORD=********
MAIL_REPLY=noreply@verusados.com
MAIL_SMTP_HOST=smtp.gmail.com
MAIL_SMTP_PORT=465
```
For google oauth settings check client_id.json

```
$ chmod -R 777 logs
$ php composer install
```
create .env & configure
create database

```
$ cd bin && php db migrate
$ cd config && zcat verusados_aux_data.sql.gz | mysql -u root -p******** verusados
```

## Adminer Editor
create .env & configure
```
$ cd public/editor && composer install
```

## Suggested Nginx configuration
```
server {

	root /var/www/ver-usados-api/public;
	...

	location / {
		try_files $uri $uri/ /index.php$is_args$args;
	}

	...
}
```

## License  

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
