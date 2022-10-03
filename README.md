# Phpy 2

Component-based PHP markup framework for rapid apps creation.

## Concepts
- Deliver & test everything fast
- Change & move code a lot
- Less code & dependencies = better
- Folders/files instead of structures to organize code
- Keep files small
- Refactor after tests & step-by-step

## Quick start
1. Clone repo into `phpy` dir:
```git clone https://github.com/mrcrypster/phpy.git```

2. Init project:
```
php phpy.php init
```

3. Code `default.php` action:
```php
<?php return phpy([
  'p' => 'some text',
  'a:/page2' => 'some page'
]]);
```

## Contribute
This is a repository with prebuilt code.
Sources and tests are available in [phpy-src repo](https://github.com/mrcrypster/phpy-src).
