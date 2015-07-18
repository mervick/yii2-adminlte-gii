# Yii2 AdminLTE Gii Extension
[![Analytics](https://ga-beacon.appspot.com/UA-65295275-1/yii2-adminlte-gii)](https://github.com/igrigorik/ga-beacon)

Yii2 Framework Code Generator Extension Gii for AdminLTE template

## Installation

This package required by [mervick/yii2-adminlte](https://github.com/mervick/yii2-adminlte).   
If you want install this manually, open terminal and run:

```bash
php composer.phar require "mervick/yii2-adminlte-gii" "*"
```
or add to composer.json
```json
"require": {
    "mervick/yii2-adminlte-gii": "*"
}
```

## Usage

Then extension was installed add the following lines in your configuration file:

```php
return [
    'bootstrap' => ['gii'],
    'modules' => [
        'gii' => [
            'class' => 'yii\gii\Module',
            'controllerNamespace' => 'mervick\adminlte\gii\controllers',
            'generators' => [
                'AdminLTE crud' => [
                    'class' => 'mervick\adminlte\gii\generators\crud\Generator',
                ],
                'AdminLTE model' => [
                    'class' => 'mervick\adminlte\gii\generators\model\Generator',
                ],
            ],
        ],
        // ...
    ],
    // ...
];
```

## Preview

![AdminLTE Gii] (http://webstyle.od.ua/test/yii/gii.png)
