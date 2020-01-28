# Beaker

WordPress testing without the cruft.  
Or "get started testing WordPress projects as fast as possible".

## Code pitch

```php
use PHPUnit\Framework\TestCase;
use lucatume\Beaker\Beaker;

class Beaker_Test extends TestCase{
    public function test_homepage_html(){
        $homepage = Beaker::fromDir('~/Sites/wp') ->get('/');

        $this->assertHtmlElement('body.home', $homepage->html());
    }
}
```

## Requirements

The library requires PHP 7.0+.
It uses [PhpUnit](https://phpunit.de/ "PHPUnit – The PHP Testing Framework") under the hood, but you need not worry about it: [Composer](https://getcomposer.org/) will install it for you.  

## Installation

Install the package using [Composer](https://getcomposer.org/) from the root directory of your WordPress project (a plugin, a theme or a site):

```bash
composer require --dev lucatume/wp-streams
```

## Getting started

WP Streams will work in a number of scenarios.  
Depending on testing setup of your project there are two major ones: one where you've not setup tests for your project yet, and one where you did set up tests.

### Starting from scratch

After you've installed Streams using Composer (as detailed in the _Installation_ section) run PHPUnit bootstrap command:

```bash
vendor/bin/phpunit --generate-configuration
```

PHPUnit default configuration is reasonable and does not require modifications.  

Create a `tests` directory in the root directory of your project and 

```bash
mkdir tests
cd tests
```

Create a first test case, using your IDE of choice, in the `tests` directory:

```php
<?php

// file tests/First_Streams_Test.php

use lucatume\WPStreams\WithWPStreams;
use Spatie\Snapshots\MatchesSnapshots;

class First_Streams_Test extends \PHPUnit\Framework\TestCase {

	use WithWPStreams;

	public function test_see_wp_homepage(){
		
		$stream = $this->stream()->get('/');

		$this	

	}

}
```


