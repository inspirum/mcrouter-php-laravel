# Memcached with Mcrouter support for Laravel

**Created as part of [inspishop][link-inspishop] e-commerce platform by [inspirum][link-inspirum] team.**

[![Latest Stable Version][ico-packagist-stable]][link-packagist-stable]
[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]
[![PHPStan][ico-phpstan]][link-phpstan]
[![Total Downloads][ico-packagist-download]][link-packagist-download]
[![Software License][ico-license]][link-licence]

[Memcached](http://memcached.org/) cache store implementation for [Laravel framework](https://github.com/laravel/framework) optimized to work with [Mcrouter](https://github.com/facebook/mcrouter).

- Static cache for [tags](https://laravel.com/docs/master/cache#cache-tags) to reduces the number of queries to the Memcached server
- Support for Mcrouter [prefix routing](https://github.com/facebook/mcrouter/wiki/Prefix-routing-setup)
- Optimized to be used in Kubernetes cluster with Memcached server on each node to achieve the lowest latency


## System requirements

* [PHP 7.1+](http://php.net/releases/7_1_0.php)
* [ext-memcached](http://php.net/memcached)


## Installation

```shell script
composer require inspirum/mcrouter
```

For Laravel 5.4 and below it necessary to register the service provider in `config/app.php`.

```php
'providers' => [
  // ...
  Inspirum\Mcrouter\Providers\McrouterServiceProvider::class,
]
```

On newer versions Laravel will automatically register via [Package Discovery](https://laravel.com/docs/master/packages#package-discovery).


### Config Files

In order to edit the default configuration you may execute:

```shell script
php artisan vendor:publish --provider="Inspirum\Mcrouter\Providers\McrouterServiceProvider"
```

After that, `config/mcrouter.php` will be created.

```php
<?php

return [
  'shared_prefix' => '/default/shr/',
  'prefixes'      => [
    '/default/a/',
    '/default/b/',
    '/default/c/',
  ],
];
```
Or you can used environment variables:

```ini
CACHE_MCROUTER_SHARED_PREFIX='/default/shr/'
CACHE_MCROUTER_PREFIXES='/default/a/,/default/b/,/default/c/'
```


## Usage example

Cache tags are automatically prefixed with Mcrouter shared prefix. 

```ini
CACHE_PREFIX='__prefix__'
CACHE_MCROUTER_SHARED_PREFIX='/default/shr/'
```
```php
cache()->tags(['bop', 'zap'])->get('foo');
```
```
get /default/shr/__prefix__:tag:bop:key
get /default/shr/__prefix__:tag:zap:key
get __prefix__:foo
```


Package support additional prefixes which can be used on Mcrouter routing prefix.

```ini
CACHE_PREFIX='__prefix__'
CACHE_MCROUTER_PREFIXES='/default/a/,/default/b/'
```
```php
cache()->get('/default/a/foo');
cache()->get('/default/b/foo');
cache()->get('/default/c/foo');
```
```
get /default/a/__prefix__:foo
get /default/b/__prefix__:foo
get __prefix__:/default/c/foo
```


### Mcrouter configuration

This configuration example is for multiple Memcached servers, one of which is local, such as a typical Kubernetes cluster. 
We only want to use the local server (on the same node as pod), if possible, to achieve the lowest latency, but to invalidate the cache key on each server.

Tagged cache flush method (`cache()->tags(['bop', 'zap'])->flush()`) do not use `delete` operation on Memcached server but update tag cached values instead.  

All operations with shared prefix (`/default/shr/`) and all `delete` operations are send to each nodes with [`AllFastestRoute`](https://github.com/facebook/mcrouter/wiki/List-of-Route-Handles#allfastestroute) handle, 
rest of the operations are send only to local server(s) with [`PoolRoute`](https://github.com/facebook/mcrouter/wiki/List-of-Route-Handles#poolroute) handle.

> Instead of `AllFastestRoute` you can use `AllSyncRoute` or `AllAsyncRoute` handle.

```json
{
  "pools": {
    "local": {
      "servers": [
        "127.0.0.1:11211"
      ]
    },
    "nodes": {
      "servers": [
        "10.80.10.1:11211",
        "10.80.10.2:11211",
        "10.80.10.3:11211"
      ]
    }
  },
  "routes": [
    {
      "aliases": [
        "/default/local/"
      ],
      "route": {
        "type": "OperationSelectorRoute",
        "default_policy": "PoolRoute|local",
        "operation_policies": {
          "delete": "AllFastestRoute|Pool|nodes"
        }
      }
    },
    {
      "aliases": [
        "/default/shr/"
      ],
      "route": "AllFastestRoute|Pool|nodes"
    }
  ]
}
```


### Kubernetes example

Example of Memcached with Mcrouter used on Kuberentes cluster with three nodes (`10.80.10.1`, `10.80.10.2`, `10.80.10.3`).

Using `DaemonSet` resource ensures that Memcached and Mcrouter will be available on each server (on ports `11211` and `11212`).

```yaml
apiVersion: extensions/v1beta1
kind: DaemonSet
metadata:
  name: memcached
spec:
  template:
    spec:
      containers:
        - name: memcached
          image: memcached:1.5-alpine
          command: ["memcached"]
          ports:
            - name: memcache
              containerPort: 11211
              hostPort: 11211
```
```yaml
apiVersion: v1
kind: ConfigMap
metadata:
  name: mcrouter
data:
  config.json: |-
    {
      "pools": {
        "local": {
          "servers": [
            "$HOST_IP:11211"
          ]
        },
        "nodes": {
          "servers": [
            "10.80.10.1:11211",
            "10.80.10.2:11211",
            "10.80.10.3:11211"
          ]
        }
      },
      "routes": [
        {
          "aliases": [
            "/default/local/"
          ],
          "route": {
            "type": "OperationSelectorRoute",
            "default_policy": "PoolRoute|local",
            "operation_policies": {
              "delete": "AllFastestRoute|Pool|nodes"
            }
          }
        },
        {
          "aliases": [
            "/default/shr/"
          ],
          "route": "AllFastestRoute|Pool|nodes"
        }
      ]
    }
```
```yaml
kind: DaemonSet
metadata:
  name: mcrouter
spec:
  template:
    spec:
      volumes:
        - name: config
          emptyDir: {}
        - name: config-stub
          configMap:
            name: mcrouter
      initContainers:
        - name: config-init
          image: alpine:latest
          imagePullPolicy: Always
          command: ['sh', '-c', 'cp /tmp/mcrouter/config.json /etc/mcrouter/config.json && sed -i "s|\$HOST_IP|${HOST_IP}|g" /etc/mcrouter/config.json']
          env:
            - name: HOST_IP
              valueFrom:
                fieldRef:
                  fieldPath: status.hostIP
          volumeMounts:
            - name: config-stub
              mountPath: /tmp/mcrouter
            - name: config
              mountPath: /etc/mcrouter
      containers:
        - name: mcrouter
          image: mcrouter/mcrouter:latest
          imagePullPolicy: IfNotPresent
          command: ["mcrouter"]
          args:
            - --port=11212
            - --config-file=/etc/mcrouter/config.json
            - --route-prefix=/default/local/
            - --send-invalid-route-to-default
          volumeMounts:
            - name: config
              mountPath: /etc/mcrouter
          ports:
            - name: mcrouter
              containerPort: 11212
              hostPort: 11212
```


You can use `status.hostIP` to inject current node IP to pod to use to connect to local Memcached/Mcrouter server.

```yaml
kind: Deployment
metadata:
  name: example
spec:
  template:
    spec:
      containers:
        - name: example
          image: alpine:latest
          env:
            - name: MCROUTER_HOST
              valueFrom:
                fieldRef:
                  fieldPath: status.hostIP
            - name: MCROUTER_PORT
              value: "11212"
```


## Testing

To run unit tests, run:

```shell script
composer test:test
```

To show coverage, run:

```shell script
composer test:coverage
```


## Contributing

Please see [CONTRIBUTING][link-contributing] and [CODE_OF_CONDUCT][link-code-of-conduct] for details.


## Security

If you discover any security related issues, please email tomas.novotny@inspirum.cz instead of using the issue tracker.


## Credits

- [Tomáš Novotný](https://github.com/tomas-novotny)
- [All Contributors][link-contributors]


## License

The MIT License (MIT). Please see [License File][link-licence] for more information.


[ico-license]:              https://img.shields.io/github/license/inspirum/mcrouter-php-laravel.svg?style=flat-square&colorB=blue
[ico-travis]:               https://img.shields.io/travis/inspirum/mcrouter-php-laravel/master.svg?branch=master&style=flat-square
[ico-scrutinizer]:          https://img.shields.io/scrutinizer/coverage/g/inspirum/mcrouter-php-laravel/master.svg?style=flat-square
[ico-code-quality]:         https://img.shields.io/scrutinizer/g/inspirum/mcrouter-php-laravel.svg?style=flat-square
[ico-packagist-stable]:     https://img.shields.io/packagist/v/inspirum/mcrouter.svg?style=flat-square&colorB=blue
[ico-packagist-download]:   https://img.shields.io/packagist/dt/inspirum/mcrouter.svg?style=flat-square&colorB=blue
[ico-phpstan]:              https://img.shields.io/badge/style-level%207-brightgreen.svg?style=flat-square&label=phpstan

[link-author]:              https://github.com/inspirum
[link-contributors]:        https://github.com/inspirum/mcrouter-php-laravel/contributors
[link-licence]:             ./LICENSE.md
[link-changelog]:           ./CHANGELOG.md
[link-contributing]:        ./docs/CONTRIBUTING.md
[link-code-of-conduct]:     ./docs/CODE_OF_CONDUCT.md
[link-travis]:              https://travis-ci.org/inspirum/mcrouter-php-laravel
[link-scrutinizer]:         https://scrutinizer-ci.com/g/inspirum/mcrouter-php-laravel/code-structure
[link-code-quality]:        https://scrutinizer-ci.com/g/inspirum/mcrouter-php-laravel
[link-inspishop]:           https://www.inspishop.cz/
[link-inspirum]:            https://www.inspirum.cz/
[link-packagist-stable]:    https://packagist.org/packages/inspirum/mcrouter
[link-packagist-download]:  https://packagist.org/packages/inspirum/mcrouter
[link-phpstan]:             https://github.com/phpstan/phpstan
