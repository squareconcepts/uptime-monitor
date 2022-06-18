# Uptime Monitor

[//]: # ([![Latest Version on Packagist]&#40;https://img.shields.io/packagist/v/squareconcepts/uptime-monitor.svg?style=flat-square&#41;]&#40;https://packagist.org/packages/squareconcepts/uptime-monitor&#41;)
[//]: # ([![Total Downloads]&#40;https://img.shields.io/packagist/dt/squareconcepts/uptime-monitor.svg?style=flat-square&#41;]&#40;https://packagist.org/packages/squareconcepts/uptime-monitor&#41;)
[//]: # (![GitHub Actions]&#40;https://github.com/squareconcepts/uptime-monitor/actions/workflows/main.yml/badge.svg&#41;)
[//]: # ()
[//]: # (This is where your description should go. Try and limit it to a paragraph or two, and maybe throw in a mention of what PSRs you support to avoid any confusion with users and contributors.)

## Installation

You can install the package via composer:

```bash
composer require squareconcepts/uptime-monitor
```

## Usage

```php
// Usage description here
    $uptimeMonitor = \Squareconcepts\UptimeMonitor\UptimeMonitor::make('https://www.google.com');
    
    if($uptimeMonitor->isHasSsl()) {
        echo 'I\'m secure!';
    } else {
        echo 'Oops, I\'m not secure!'
    }

    if($uptimeMonitor->isOnline()) {
        echo 'I\'m online!';
    } else {
        echo 'Oops, I\'m offline!'
    }
    
    echo $uptimeMonitor->expiresIn(); // 20 days from now
   
```

If you'd like to have a number in stead of the string
```php
    $months = $uptimeMonitor->expiresIn('months');
    $weeks = $uptimeMonitor->expiresIn('weeks');
    $days = $uptimeMonitor->expiresIn('days');
    $hours = $uptimeMonitor->expiresIn('hours');
    $minutes = $uptimeMonitor->expiresIn('minutes');
    $seconds = $uptimeMonitor->expiresIn('seconds');
```


### Testing

```bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.


### Security

If you discover any security related issues, please email arthur@squareconcepts.nl instead of using the issue tracker.

## Credits

-   [Arthur Doorgeest](https://github.com/squareconcepts)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
