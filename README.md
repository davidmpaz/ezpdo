Introduction
============


##*Overview*

This is the EZPDO orm framework started by Oak Nauhygon <ezpdo4php@gmail.com>. Unless stated the contrary, most of this, is part of his work. Since the former site (<http://www.ezpdo.net>) ceased to exist we will reproduce the framework documentation and also continuing giving it support here.

You can find this same documentation and more on the [wiki][] pages.


##*Motivation*

Object-relational mapping [ORM][] is useful in developing both Web-based and non-Web-based applications.

The goal of this project is to design a lightweight and easy-to-use persistence solution for PHP. This is how the project got its name, **E**a**s**y **P**HP **D**ata **O**bjects (EZPDO). Simplicity is the key. Constantly we keep the following requirements in mind when designing EZPDO.

* It requires minimun or zero <a name="fn1" href="#fnt1">(1)</a> SQL knowledge and a minimum amount of effort in ORM specification.
* It should work with existing classes and does not alter the source code.
* It should only introduce overhead to a minimum to guarantee performance.


##*Features*

Here is a quick look at the features in EZPDO before you delve into any details. 

- Minimum SQL knowledge required
- Requires minimum ORM specification
- No Phing! No need of explicit command line compile
- Works with existing code and database
- Has a small runtime core to guarentee performance
- Handles 1:N, and M:N relationships automatically
- Provides a simple runtime API
- Supports object query ([EZOQL][ezoql])
- Auto generates database tables
- Test-driven with continuous integration. [![Thanks to Travis!!](https://api.travis-ci.org/davidmpaz/ezpdo.png?branch=master)](https://travis-ci.org/davidmpaz/ezpdo)

##*Licence*

EZPDO is an [open source][os] project and uses the [BSD][] license.

----

Quickstart
==========

Let us start with a simple example [click here][quick] to show you how easy it is to use EZPDO to persist and retrieve your objects.

You may also want to check out the [tutorial][tut] that showcases more advanced features.

Wanna do more?
==============

You have followed the simple example above and got the first taste of EZPDO. Easy, isnt it? Want to do more with EZPDO? Follow the links below.

##*Installation*
[The Instalation Guide][install]

##*Tutorial*
[A tutorial to get you started][tut]

##*User manual*
[All you need to know to use EZPDO][manual]

##*Developer's guide*
[A guide to those who are interested in EZPDO internals][dev]

##*Migrating from php 5.2*
[Small "how to" when moving from v5.2 to v5.3 of php][migrate]

##*FAQ*
[FAQs][faq]

##*References*
[Articles, blogs and projects that have impact on EZPDO][ref]

##*Contact*

Need help, want to help, or have comments? Clone or Fork [this][project] project, we'll look forward for your pull requests or issues. 

----

##*Notes*:

><a name="fnt1" href="#fn1">(1)</a> Please don’t misunderstand. We are not saying “Down with SQL!” :) Nothing wrong with having a solid knowledge of SQL. In fact, it helps you in many aspects if you have good SQL skills, especially when it comes to efficient [object query][ezoql], however most of the time with EZPDO you don’t need to simply because it takes care of interfacing with databases without you even knowing.


[ORM]: http://www.service-architecture.com/object-relational-mapping/ "Object Relational Mapping"
[ezoql]: https://github.com/davidmpaz/ezpdo/wiki/EZOQL "EZPDO Object Query Language"
[os]: http://opensource.org/index.php
[BSD]: https://github.com/davidmpaz/ezpdo/wiki/BSD "BSD License"
[quick]: https://github.com/davidmpaz/ezpdo/wiki/Quickstart "Quickstart Example"
[tut]: https://github.com/davidmpaz/ezpdo/wiki/Tutorial "Advanced Example"
[install]: https://github.com/davidmpaz/ezpdo/wiki/Installation "Installation Guide"
[manual]: https://github.com/davidmpaz/ezpdo/wiki/UserManual "User Manual"
[dev]: https://github.com/davidmpaz/ezpdo/wiki/Developers "For those who wants to contribute"
[faq]: https://github.com/davidmpaz/ezpdo/wiki/Faqs "Frequently Asked Questions"
[ref]: https://github.com/davidmpaz/ezpdo/wiki/References
[project]: https://github.com/davidmpaz/ezpdo
[wiki]: https://github.com/davidmpaz/ezpdo/wiki
[migrate]: https://github.com/davidmpaz/ezpdo/wiki/Ezpdo-conservative-migration-from-php-5.2-to-php-5.3

