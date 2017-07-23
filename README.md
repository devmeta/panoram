# Panoram
Timelapse web app 

# Panoram APP Installation (Front-end)

![Panoram](https://panoram.devmeta.net/images/iso-panoram-big.png)

[![Latest Version](https://img.shields.io/packagist/v/tuupola/slim-skeleton.svg?style=flat-square)](https://github.com/tuupola/slim-skeleton/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

This is Slim 3 API skeleton panoram for Composer. Project uses [Spot](http://phpdatamapper.com/) as persistence layer,  [Monolog](https://github.com/Seldaek/monolog) for logging, and [Fractal](http://fractal.thephpleague.com/) as serializer. [Vagrant](https://www.vagrantup.com/) virtualmachine config and [Paw](https://geo.itunes.apple.com/us/app/paw-http-rest-client/id584653203?mt=12&at=1010lc2t) panoram files are included for easy development.

## Install

Install [composer](https://getcomposer.org/) or type

``` bash
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
```

## Usage

If you have [Vagrant](https://www.vagrantup.com/) installed start the virtual machine.

``` bash
cd app
vagrant up
```

Now you can access the api at [https://192.168.50.52/todos](https://192.168.50.52/todos)

``` bash
# nano .env
```

### Paste and edit the asterisks.
``` php
BUCKET_URL=https://panoram.buk
APP_TITLE="Panoram | "

```
### Set up the web app config file and point to the api endpoints

```
nano public/js/config.js
```

``` javascript
var endpoint = 'https://api.panoram.net';
var bucket_url = 'https://bucket.exmaple.net';
```

``` bash
chmod -R 777 logs
php composer install
```


# Panoram API Installation
####
![Panoram](https://panoram.devmeta.net/images/iso-panoram-big.png)

[![Latest Version](https://img.shields.io/packagist/v/tuupola/slim-skeleton.svg?style=flat-square)](https://github.com/tuupola/slim-skeleton/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

This is Slim 3 API skeleton panoram for Composer. Project uses [Spot](http://phpdatamapper.com/) as persistence layer,  [Monolog](https://github.com/Seldaek/monolog) for logging, and [Fractal](http://fractal.thephpleague.com/) as serializer. [Vagrant](https://www.vagrantup.com/) virtualmachine config and [Paw](https://geo.itunes.apple.com/us/app/paw-http-rest-client/id584653203?mt=12&at=1010lc2t) panoram files are included for easy development.

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

### Create an empty file with name .env and set up your environment


``` bash
# nano .env
```

### Paste and edit the asterisks.
``` php
DEBUG_LEVEL=WARNING
APP_TITLE=Site title
APP_URL=http://panoram.net
APP_REDIRECT_AFTER_LOGIN=/
APP_AD_DUE="65 days"
APP_LISTING_PER_PAGE=20
APP_IMAGE_UPLOAD_MAX=5000
APP_HASH_SALT=********
APP_JWT_SECRET=********
APP_JWT_EXPIRATION="30 minutes"
API_CURRENCY_ID=********
BUCKET_URL=https://bucket.panoram.net
DB_NAME=panoram
DB_USER=root
DB_PASSWORD=********
DB_HOST=localhost
FB_APP_ID=********
FB_APP_SECRET=********
S3_EXTENSIONS=jpeg,jpg,png,gif
S3_RESOLUTIONS=120x80,480x360,800x600
S3_PROFILE_RESOLUTIONS=80x80,200x200
S3_EXTENSION=jpg
S3_QUALITY=90
MAIL_CONTACT=contact@panoram.net
MAIL_FROM=noreply@panoram.net
MAIL_FROM_NAME="El Equipo Panoram"
MAIL_SMTP_ACCOUNT=account@gmail.com
MAIL_SMTP_PASSWORD=********
MAIL_SMTP_HOST=smtp.gmail.com
MAIL_SMTP_AUTH=true
MAIL_SMTP_SECURE=ssl
MAIL_SMTP_DEBUG=0
MAIL_SMTP_PORT=465
```
### For google oauth settings check this file 
``` bash
nano api/config/client_id.json
```

## Set permissions and run composer.
``` bash
chmod -R 777 logs
php composer install
```
## Create your database mannually

``` bash
mysql -u root -p********
```

``` mysql
Type 'help;' or '\h' for help. Type '\c' to clear the current input statement.

mysql> CREATE DATABASE panoram;
```

## Run the migrate.
``` bash
cd bin && php db migrate
cd config && zcat Panoram_aux_data.sql.gz | mysql -u root -p******** panoram
```

## License  

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.