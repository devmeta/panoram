# panoram APP (Front-end)

![panoram](https://bytebucket.org/franciscomarasco/panoram-app/raw/a677f6a0a6d085eb1e2e8a382d5dc4a1befe4e65/public/images/iso-panoram-big.png?token=dcf3c3eeba60d50d3d365f5a247b7d1bd8f36408)

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

Now you can access the api at [https://192.168.50.52/todos](https://192.168.50.52/todos)

### Configure api endpoint in [public/js/config.php]

```
var endpoint = 'https://api.panoram.com';
```

```
chmod -R 777 logs
php composer install
```


## Suggested Nginx configuration
```
server {
	root /var/www/panoram-app/public;
	...

	location / {
		try_files $uri $uri/ /index.php$is_args$args;
	}

	...
}
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.