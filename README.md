# MK VARNISH

[![Latest Stable Version](https://img.shields.io/packagist/v/dmk/mkvarnish.svg?maxAge=3600&style=flat-square)](https://packagist.org/packages/dmk/mkvarnish)
[![Total Downloads](https://img.shields.io/packagist/dt/dmk/mkvarnish.svg?maxAge=3600&style=flat-square)](https://packagist.org/packages/dmk/mkvarnish)
[![Build Status](https://img.shields.io/travis/DMKEBUSINESSGMBH/mkvarnish.svg?maxAge=3600&style=flat-square)](https://travis-ci.org/DMKEBUSINESSGMBH/mkvarnish)
[![License](https://img.shields.io/packagist/l/dmk/mkvarnish.svg?maxAge=3600&style=flat-square)](https://packagist.org/packages/dmk/mkvarnish)


> Varnish Cache is a web application accelerator also known as a caching HTTP reverse proxy.
> You install it in front of any server that speaks HTTP and configure it to cache the contents.
> Varnish Cache is really, really fast.
> It typically speeds up delivery with a factor of 300 - 1000x, depending on your architecture.
> A high level overview of what Varnish does can be read on [varnish-cache.org](http://varnish-cache.org/)


## Introduction


### What does it do?

This extension tells Varnish about TYPO3 insights of a page to allow Varnish
make proper caching decisions based on those information.
It informs Varnish to invalidate the cache
as soon as the content is changed through the TYPO3 backend.


### Features

 *  ready to use configration for Varnish and TYPO3
 *  Varnish based caching for all pages using cache-tags
 *  TYPO3 clear cache hook to clear cache or smart ban relevant pages in Varnish


### Background

 *  the extension sets `config.sendCacheHeaders = 1`
    to enable TYPO3 core function which sends appropriate cache headers to Varnish
 *  send "X-Cache-Tags" HTTP Header which is used to issue PURGE command against
 *  send appropriate PURGE Command to Varnish during a TYPO3 clearCache action
 *  those headers are used for Varnish processing only and get removed afterwards


## Installation

We recommend the installation via composer.
Maybe you can use our [TYPO3-Composer-Webroot Project](https://github.com/DMKEBUSINESSGMBH/typo3-composer-webroot)

From project root you need to run
```bash
composer require dmk/mkvarnish
```


### Requirements

 *  you should make yourself familiar with Varnish and how you want to implement Varnish in your specific setup.
 *  Varnish has to be up and running. You can find a sample configuration in `Configuration/Varnish`.
 *  requests to all static files should send appropriate expires headers


### Configuration

 *  set `$TYPO3_CONF_VARS['SYS']['reverseProxyIP']` to the IP address
    which is used by Varnish to connect to your Webserver ore enable the caching in the extension configuration by set `sendCacheHeaders` to `Force Enabled`.
 *  do not use sessions, the fe_typo_user cookie will disable the caching
 *  dont set `no_cache=1`
 *  the use of *_INT objects will disable the cache too, dont use it
