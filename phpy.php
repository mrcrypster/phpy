<?php


# --- Wrappers ---

function phpy(...$args) {
  $root = $args[0];
  phpy::launch($root);
  
}



# --- Implementation ---

class phpy {
  
  # --- configuration ---
  
  private static $root;
  
  
  
  
  # --- core implementation ---
  
  # Launch PHPu app, specify "public" folder full path as $root
  public static function launch($root) {
    self::$root = dirname($root);
    
    header('Content-type: text/html;charset=utf-8');
    $output = self::com('layout');
    echo implode('', $output);
  }
  
  # Execute component by $path
  public static function com($path, $args = []) {
    $file = self::$root . '/coms/' . $path . '.php';
    if ( is_file($file) ) {
      $data = include $file;
      return self::exec($data);
    }
    else {
      $class = $path . '_com_phpy';
      if ( class_exists($class) ) {
        $output = self::exec( call_user_func($class . '::exec', $args) );
        return implode('', $output);
      }
      else {
        return self::default_com([$path => $args]);
      }
    }
  }
  
  # Execute component by data
  public static function exec($data) {
    
    if ( !is_array($data) ) return [$data];
    
    $output = [];
    foreach ( $data as $k => $sub_data ) {
      if ( is_numeric($k) ) {
        foreach ( self::exec($sub_data) as $o ) {
          $output[] = $o;
        }
      }
      else {
        $output[] = self::com($k, $sub_data);
      }
    }
    return $output;
  }
  
  # Default component handler
  public static function default_com($data) {
    if ( is_string($data) ) {
      return htmlspecialchars($data);
    }
    else {
      $output = '';
      foreach ( $data as $tag => $params ) {
        list($attrs, $data) = self::params($params);
        $attr_str = [];
        foreach ( $attrs as $k => $v ) {
          $attr_str[] = $k . '="' . htmlspecialchars($v) . '"';
        }
        
        $inner = [];
        if ( is_array($data) ) {
          foreach ( $data as $k => $v ) {
            foreach ( self::exec([$k => $v]) as $i ) {
              $inner[] = $i;
            }
          }
        }
        else {
          $inner[] = self::default_com($data);
        }
        
        $output .= '<' . $tag . ( $attr_str ? ' ' . implode(' ', $attr_str) : '') . '>' . implode('', $inner). '</' . $tag . '>';
      }
      
      return $output;
    }
  }
  
  # Parse params and split into 2 maps: attrs and other data
  public static function params($params) {
    if ( !is_array($params) ) return [[], $params];
    
    $attr_names = ['href', 'id', 'class', 'value', 'placeholder', 'src'];
    
    $attrs = $data = [];
    foreach ( $params as $k => $v ) {
      if ( !is_numeric($k) && in_array($k, $attr_names) ) {
        $attrs[$k] = $v;
      }
      else {
        $data[$k] = $v;
      }
    }
    
    return [$attrs, $data];
  }
}



# --- Built-in components
class htm_com_phpy {
  public static function exec($data) {
    return [
      ['raw' => '<!DOCTYPE html>'],
      'html' => [
        'head' => [
          'title' => 'Hi!',
          'raw' => '<meta name="viewport" content="width=device-width, initial-scale=1.0">' .
                   '<link rel="stylesheet" href="/styles.css">'
        ],
        'body' => $data,
        'script' => ['src' => '/script.js']
      ],
    ];
  }
}

class raw_com_phpy {
  public static function exec($data) {
    return $data;
  }
}