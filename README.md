yii2-firephp
============

FirePHP log target for YII2

If it does not work, try adding "ob_start ()" in index.php

Usage
-----

composer.json

	"require": {
        	"codeexpert/yii2-firephp": "0.1.*@dev"
	},

config file:

    $config = [
        'components' => [
            'log' => [
                'targets' => [
                    'firephp' => [
                        'class' => 'codeexpert\log\FirePHPTarget',
                    ]
                ],
            ],
        ],
    ];


anywhere:

    \Yii::info('Hello, I am info message', 'test1');
    \Yii::warning('Hello, I am warning message', 'test2');
    \Yii::error('Hello, I am error message', 'test3');
    \Yii::info(array('a'=>'b', 'c'=>'d'), 'array test');
