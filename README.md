# Mutex

[![Latest stable version](https://poser.pugx.org/geoffroy-aubry/Mutex/v/stable.png "Latest stable version")](https://packagist.org/packages/geoffroy-aubry/Mutex)

Mutex & Semaphore PHP implementations both based only on flock() function.

## Demo

### Mutex

File: [demo-mutex.php](examples/demo-mutex.php).

Example:
```php
$oLogger = new MinimalLogger();
$oMutex = new Mutex($oLogger, 100, '/tmp/demo-lock');

$oMutex->acquire();
echo "Do anything for 3 seconds…\n";
sleep(3);
$oMutex->release();
```
⇒ RESULT for 2 processes P1 and P2:
```php
P1 $ php examples/demo-mutex.php
Do anything for 3 seconds…

P2 $ php examples/demo-mutex.php
Waiting to acquire Mutex lock on /tmp/demo-lock…
Mutex lock acquired after 2.57s
Do anything for 3 seconds…
```

### Semaphore

File: [demo-semaphore.php](examples/demo-semaphore.php).

Example, semaphore with 2 units of a resource `/tmp/demo-sem`:
```php
$oLogger = new MinimalLogger();
$oSem = new Semaphore($oLogger, 2, 100, '/tmp/demo-sem');

$oSem->acquire();
echo "Do anything for 3 seconds…\n";
sleep(3);
$oSem->release();
```
⇒ RESULT for 3 processes P1, P2 and P3:
```php
P1 $ php examples/demo-semaphore.php
Do anything for 3 seconds…

P2 $ php examples/demo-semaphore.php
Do anything for 3 seconds…

P3 $ php examples/demo-semaphore.php
Waiting to acquire lock on /tmp/demo-sem…
Lock acquired after 2.30s
Do anything for 3 seconds…
```

## Usage

**Mutex** is available via [Packagist](https://packagist.org/packages/geoffroy-aubry/mutex).

1. Class autoloading and dependencies are managed by [Composer](http://getcomposer.org/) 
so install it following the instructions 
on [Composer: Installation - *nix](http://getcomposer.org/doc/00-intro.md#installation-nix)
or just run the following command:
```bash
$ curl -sS https://getcomposer.org/installer | php
```

2. Add dependency to `GAubry\Mutex` into require section of your `composer.json`:
```json
    {
        "require": {
            "geoffroy-aubry/mutex": "1.*"
        }
    }
```
and run `php composer.phar install` from the terminal into the root folder of your project.

3. Include Composer's autoloader and use the `GAubry\Mutex` classes.

## Copyrights & licensing
Licensed under the GNU Lesser General Public License v3 (LGPL version 3).
See [LICENSE](LICENSE) file for details.

## Change log
See [CHANGELOG](CHANGELOG.md) file for details.

## Git branching model
The git branching model used for development is the one described and assisted by `twgit` tool: [https://github.com/Twenga/twgit](https://github.com/Twenga/twgit).
