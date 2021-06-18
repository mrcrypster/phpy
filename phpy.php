<?php

class phpy {
  private static $app_path;
  private static $com_path;
  
  /**
   * Run application
   */
  public static function run($app_path) {
    ob_start();
    
    self::$app_path = $app_path;
    self::$com_path = dirname(realpath($app_path)) . '/com';
    
    $data = [
      'content_type' => 'html'
    ];
    
    
    # Run top level index handler, if declared
    $index_action_file = $app_path . '/' . 'index.php';
    if ( is_file($index_action_file) ) {
      $index_data = include $index_action_file;
      if ( is_array($index_data) ) {
        foreach ( $index_data as $k => $v ) {
          $data[$k] = $v;
        }
      }
    }
    
    
    # Run action handler, based on URI
    /*$action = str_replace('.', '_', trim(parse_url($_SERVER['REQUEST_URI'])['path'], '/'));
    if ( $action ) {
      $action_data = self::action($action);
      
      if ( ($_SERVER['REQUEST_METHOD'] == 'POST') || $action_data['back'] ) {
        die(header('Location: ' . $_SERVER['HTTP_REFERER']));
      }
      
      # Merge top-level and action data
      if ( $action_data ) {
        foreach ( $action_data as $k => $v ) {
          $data[$k] = $v;
        }
      }
    }*/
    
    
    # Render data
    self::render('index', $data);
    
    ob_flush();
  }
  
  
  /**
   * Execute action handler
   */
  public static function action($action) {
    $action_data = null;
    
    foreach ( [self::$app_path, self::$com_path] as $path ) {
      $action_file = $path . '/' . $action . '.php';
      if ( is_file($action_file) ) {
        $action_data = include $action_file;
      }
      else {
        $action_file = $path . '/' . $action . '/index.php';
        if ( is_file($action_file) ) {
          $action_data = include $action_file;
        }
      }
    }
    
    return is_array($action_data) ? $action_data : null;
  }
  
  /**
   * Render view with specified data
   */
  public static function render($view, $data = []) {
    foreach ( [self::$app_path, self::$com_path] as $path ) {
      $view_file = $path . '/' . $view . '.html.php';
      if ( !is_file($view_file) ) {
        $view_file = $path . '/' . $view . '/index.html.php';
      }
      
      if ( !is_file($view_file) ) {
        continue;
      }
      
      if ( $data ) {
        foreach ( $data as $k => $v ) $$k = $v;
      
        if ( $data['content_type'] == 'html' ) {
          header('Content-type: text/html;charset=utf-8');
        }
      }
      
      include $view_file;
    }
  }
  
  
  /**
   * Run and render isolated component
   */
  public static function com($uri = null) {
    if ( is_null($uri) ) {
      $uri = parse_url($_SERVER['REQUEST_URI'])['path'];
    }
    
    $action = str_replace('.', '_', trim($uri, '/'));
    if ( !$action ) {
      $action = 'default';
    }
    
    $action_data = self::action($action);
    
    if ( $action_data['back'] ) {
      die(header('Location: ' . $_SERVER['HTTP_REFERER']));
    }
    
    self::render($action, $action_data);
  }
}