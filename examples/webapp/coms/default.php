<?php return [
  'p' => 'Hi, I am the defauly page!',
  'a' => [
    'href' => 'javascript:;',
    
    'do' => function($data) {
      return '1';
    },
    
    'data' => [
      'id' => 7,
      'number' => random_number()
    ],
    
    'Test Ajax'
  ]
];