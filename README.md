<div align="center">

# The Poakium Annotations

[![Latest Version](https://img.shields.io/packagist/v/biurad/annotations?include_prereleases&label=Latest&style=flat-square)](https://packagist.org/packages/biurad/annotations)
[![Workflow Status](https://img.shields.io/github/actions/workflow/status/biurad/poakium/ci.yml?branch=master&label=Workflow&style=flat-square)](https://github.com/biurad/poakium/actions?query=workflow)
[![Software License](https://img.shields.io/badge/License-BSD--3-brightgreen.svg?&label=Poakium&style=flat-square)](LICENSE)
[![Maintenance Status](https://img.shields.io/maintenance/yes/2023?label=Maintained&style=flat-square)](https://github.com/biurad/poakium)

</div>

---

A lightweight [PHP][1] library providing simple, fast, and easy use of [Doctrine Annotations][2] and [Attributes][3] support for your [PHP][1] projects.
This can help to improve code organization, reduce the risk of errors, and make it easier to maintain and update code over time. From [PHP][1] 7.2 to 7.4 projects requires installing [`spiral/attributes`][4].

## 📦 Installation

This project requires [PHP][1] 7.2 or higher. The recommended way to install, is by using [Composer][5]. Simply run:

```bash
$ composer require biurad/annotations
```

## 📍 Quick Start

This library acts as a manager enabling you use all your annotations/attributes in one place. Add all resources you want fetch annotations/attributes from using the `resource()` method. Then add your listener(s) which will listeners to your annotated/attributed implementation. Use the `load()` method when you want to use the returned result of a specific listener. **(NB: Listeners can be named)**.

Here is an example of how to use the library:

```php
use Biurad\Annotations\AnnotationLoader;
use Spiral\Attributes\AnnotationReader;
use Spiral\Attributes\AttributeReader;
use Spiral\Attributes\Composite\MergeReader;

// The doctrine annotation reader requires doctrine/annotations library
$doctrine = new AnnotationReader();

// With spiral/attributes library, we can use PHP 8 attributes in PHP 7.2 +
$attribute = new AttributeReader();

// Create a new annotation loader from readers ...
$annotation = new AnnotationLoader(new MergeReader([$doctrine, $attribute]));

$annotation->listener(...); // Add your implemented Annotation listeners

$annotation->resource(...); // Add a class/function string, class file, or directory

$listeners = $annotation->load(); // Compile once, then load cached ...

// To use a collector you implemented into your instance of `Biurad\Annotations\ListenerInterface`
foreach ($listeners as $collector) {
    // You can fetch the required $collector from here.
}
```

> **NB:** If you are on [PHP][1] 8 and wishes to use attributes only, please avoid using [`spiral/attributes`][4] package for best performance, contributing to why this library was not shipped with [`spiral/attributes`][4] package.

## 📓 Documentation

In-depth documentation on how to use this library can be found at [docs.biurad.com][6]. It is also recommended to browse through unit tests in the [tests](./tests/) directory.

## 🙌 Sponsors

If this library made it into your project, or you interested in supporting us, please consider [donating][7] to support future development.

## 👥 Credits & Acknowledgements

- [Divine Niiquaye Ibok][8] is the author this library.
- [All Contributors][9] who contributed to this project.

## 📄 License

Poakium Annotations is completely free and released under the [BSD 3 License](LICENSE).

[1]: https://php.net
[2]: https://github.com/doctrine/annotations
[3]: https://php.watch/versions/8.0/attributes
[4]: https://github.com/spiral/attributes
[5]: https://getcomposer.org
[6]: https://docs.biurad.com/poakium/annotations
[7]: https://biurad.com/sponsor
[8]: https://github.com/divineniiquaye
[9]: https://github.com/biurad/php-annotations/contributors
