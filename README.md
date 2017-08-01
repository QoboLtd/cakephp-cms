# Cms plugin for CakePHP

[![Build Status](https://travis-ci.org/QoboLtd/cakephp-cms.svg?branch=master)](https://travis-ci.org/QoboLtd/cakephp-cms)
[![Latest Stable Version](https://poser.pugx.org/qobo/cakephp-cms/v/stable)](https://packagist.org/packages/qobo/cakephp-cms)
[![Total Downloads](https://poser.pugx.org/qobo/cakephp-cms/downloads)](https://packagist.org/packages/qobo/cakephp-cms)
[![Latest Unstable Version](https://poser.pugx.org/qobo/cakephp-cms/v/unstable)](https://packagist.org/packages/qobo/cakephp-cms)
[![License](https://poser.pugx.org/qobo/cakephp-cms/license)](https://packagist.org/packages/qobo/cakephp-cms)
[![codecov](https://codecov.io/gh/QoboLtd/cakephp-cms/branch/master/graph/badge.svg)](https://codecov.io/gh/QoboLtd/cakephp-cms)

## Requirements

**Plugins:**
- [Cakephp-Tinymce-Elfinder](https://github.com/hashmode/cakephp-tinymce-elfinder)
- [UseMuffin/Slug](https://github.com/UseMuffin/Slug)
- [UseMuffin/Trash](https://github.com/UseMuffin/Trash)

## Setup

You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

The recommended way to install composer packages is:

Install plugin

```
composer require qobo/cakephp-cms
```

Load required plugins

```
bin/cake plugin load Qobo/Utils --bootstrap
bin/cake plugin load Muffin/Trash
bin/cake plugin load Muffin/Slug
bin/cake plugin load Burzum/FileStorage
bin/cake plugin load CakephpTinymceElfinder --routes
```

Load plugin

```
bin/cake plugin load Cms --routes --bootstrap

```

Run migrations

```
bin/cake migrations migrate -p Burzum/FileStorage
bin/cake migrations migrate -p Cms
```

Configure AdminLTE theme as per the instructions in
[Qobo/Utils](https://github.com/QoboLtd/cakephp-utils/) plugin.

Load CakePHP TinyMCE elFinder helper from `initialize()` method of `src/View/AppView.php`:

```php
public function initialize()
{
    $this->loadHelper('Form', ['className' => 'AdminLTE.Form']);
    $this->loadHelper('CakephpTinymceElfinder.TinymceElfinder');
}
```

Navigate to `/cms/sites/` to get started.

## WYSIWYG editor

The plugin's WYSIWYG editor is [tinyMCE 4.*](https://www.tinymce.com) which is used to create/edit the article content.

## Documentation

For documentation see the [docs](docs/README.md) directory of this repository.
