# PHPy2

PHPy is built for prototyping web apps, focusing on delivering apps to end-users in as little time as possible. Creating prototypes is different from (re)building apps based on known requirements. Prototyping means not only testing ideas, but delivering changes fast in situation of rapid evolution.

```php
<?php return [
  'h1' => 'Hi',
  'p.text' => 'This is paragraph',
  
  'ul#list' => [
    ['li' => 'First item'],
    ['li' => 'Second item']
  ],
  
   'form:/signup' => [
    'input:email' => 'test@example.com',
    
    
    'select:type' => [
      1 => 'Personal',
      2 => 'Business'
    ],
    
    'submit' => 'Sign up',
  ],
  
  'a:/home' => ['Return home', ':rel' => 'nofollow']
];
```

## Concepts
- Use native language features.
- Do not use big frameworks & libs.
- The less code the better.
- Use little or no third-party dependencies.
- Organize endpoints based on files.
- Move code a lot accross app.
- Keep files small, split and nest big files.
- Self-explanatory code instead of comments.

## Features
- PHP-based markup.
- File based actions router.
- Simplified syntax for most HTML elements.
- Nest actions inside other actions.
- Render directly to DOM elements from JS.
- Create custom markup elements.
- Client-server Pub/sub queue
- Custom endpoints handlers
- Integrated [boilerplate CSS](https://github.com/mrcrypster/cssy-src/tree/main)


## Quick start
```bash
git clone https://github.com/mrcrypster/phpy.git
php phpy/phpy.php init /path/to/newapp
```
And continue with the <a href="https://phpy.dev/guide">Building Web App Guide</a> or <a href="https://phpy.dev/docs">PHPy Reference</a>.

## Contribute
This is a repository with prebuilt code.
Sources and tests are available in [phpy-src repo](https://github.com/mrcrypster/phpy-src).
