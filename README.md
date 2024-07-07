Threads library for PHP
=========================
Threads is a powerful and user-friendly PHP library designed to simplify parallel processing in PHP applications. By abstracting complex threading capabilities into straightforward, easy-to-use functions, Threads enables developers to enhance performance and efficiency in their applications with minimal effort.

We encourage you to enbrace passing parameters to the method as array of key-values.
Use descriptive keys and provide [$defaults] always to avoid need for documentation/manual(s) 

Install
-------

> composer require rapttor/threads

may need to add to composer.json

> "minimum-stability": "dev",
> "prefer-stable": true,

Features
--------
* PSR-4 autoloading compliant structure
* Unit-Testing with PHPUnit
* Comprehensive Guides and tutorial
* Easy to use to any framework or even a plain php file

I encourage that you put more information on this readme file instead of leaving it as is. See [rapttor.com](http://www.rapttor.com/) for more info.

Licence (Available in a separate file)
-------
MIT License

Example
-------

    <?php 
    // composer require rapttor/threads
    require_once("./vendor/autoload.php");

    \RapTToR\Threads::run(array(
        "process" => function ($data = false) {
            for ($i = 0; $i < 3; $i++) {
                echo "A $i" . PHP_EOL;
                sleep(1);
            }
        }
    ));


    \RapTToR\Threads::run(array(
        "process" => function ($data = false) {
            for ($i = 0; $i < 3; $i++) {
                echo "BB $i" . PHP_EOL;
                sleep(2);
            }
            return "BB RUN DONE; " . PHP_EOL;
        },
        "response" => function ($data) {
            var_dump('BB response: ', $data);
        }
    ));

    // if you would like to wait...
    pcntl_wait($status);
    echo "app done.";

Test output: 
------------
```
main process pid = N0 
starting process: N1
starting process: N2
string(7) "run A: "
bool(false)
A 0
string(7) "run B: "
bool(false)
BB 0
A 1
BB 1
A 2
N1 process finished
N2 process finished
BB 2
N0 process finished
app done.
```
