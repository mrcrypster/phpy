# Phpy 2

Component-based PHP markup framework for rapid apps creation.

## Concepts
- the less code and dependencies the better
- you move [small and big] parts of code from place to place frequently
- organize code based on files, keep each file as small as possible
- ... work in progress

## Quick start
First clone repo into `phpy` dir:
```git clone https://github.com/mrcrypster/phpy.git```

Now use:
```php
echo phpy(['html' => [
  ':title' => 'Hi',
  '#content' => [
    'p' => 'some text',
    'a:/page2' => 'some page'
  ]
]]);
```

## Contribute
This is a repository with prebuilt code.
Sources and tests are available in [phpy-src repo](https://github.com/mrcrypster/phpy-src).
