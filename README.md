[![Latest Stable Version](https://poser.pugx.org/pmvc-plugin/auth/v/stable)](https://packagist.org/packages/pmvc-plugin/auth) 
[![Latest Unstable Version](https://poser.pugx.org/pmvc-plugin/auth/v/unstable)](https://packagist.org/packages/pmvc-plugin/auth) 
[![Build Status](https://travis-ci.org/pmvc-plugin/auth.svg?branch=master)](https://travis-ci.org/pmvc-plugin/auth)
[![License](https://poser.pugx.org/pmvc-plugin/auth/license)](https://packagist.org/packages/pmvc-plugin/auth)
[![Total Downloads](https://poser.pugx.org/pmvc-plugin/auth/downloads)](https://packagist.org/packages/pmvc-plugin/auth) 

auth
===============

## Login Process
call loginBegin -> 
ask third party server -> 
call loginFinish -> 
handle authorization result

## Check Login status 
1. isAuthorized
2. isRegisted

## How work
### 1. go to index
   * Gen return url
   * Ask third party remote server
   * Third party server back return result to return url
### 2. Return url
   * Check third party server run result
   * if succcess redirect to success page else go to error page

### 3. Success page or Error page

## Install with Composer
### 1. Download composer
   * mkdir test_folder
   * curl -sS https://getcomposer.org/installer | php

### 2. Install by composer.json or use command-line directly
#### 2.1 Install by composer.json
   * vim composer.json
```
{
    "require": {
        "pmvc-plugin/auth": "dev-master"
    }
}
```
   * php composer.phar install

#### 2.2 Or use composer command-line
   * php composer.phar require pmvc-plugin/auth

