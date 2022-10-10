# PHPy 2

PHPy is built for prototyping, focusing on delivering apps to end-users in as little time as possible. Creating prototypes is different from (re)building apps based on known requirements. Prototyping means not only testing ideas, but delivering changes fast in situation of rapid evolution.

That's why we rely on the following:

## Concepts
- Use native language features.
- Do not use big frameworks & libs.
- Write less code.
- Use little or no third-party dependencies.
- Organize code in files rather than object structures.
- Change & move code a lot accross app.
- Keep files small, split big files and nest parts.
- Do not comment code, but make it self-readable.

## Quick start
1. Clone repo:
```bash
git clone https://github.com/mrcrypster/phpy.git
```

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

4. [Setup web server](#howto) and open your web app in browser.

## Contribute
This is a repository with prebuilt code.
Sources and tests are available in [phpy-src repo](https://github.com/mrcrypster/phpy-src).
