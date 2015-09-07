# Mutex

[![Latest stable version](https://poser.pugx.org/geoffroy-aubry/Mutex/v/stable.png "Latest stable version")](https://packagist.org/packages/geoffroy-aubry/Mutex)

Mutex & Semaphore PHP implementations both based only on flock() function.

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

3. Include Composer's autoloader and use the `GAubry\Mutex` classes: 
[Mutex demo](examples/demo-mutex.php), 
[Semaphore demo](examples/demo-semaphore.php).


## Copyrights & licensing
Licensed under the GNU Lesser General Public License v3 (LGPL version 3).
See [LICENSE](LICENSE) file for details.

## Change log
See [CHANGELOG](CHANGELOG.md) file for details.

## Git branching model
The git branching model used for development is the one described and assisted by `twgit` tool: [https://github.com/Twenga/twgit](https://github.com/Twenga/twgit).
