<?php

# Core framework class implementation



class phpy {
  
  # configuration
  private static $config = [
    'route' => [
      '*.css' => 'css',
      '*.js' => 'js'
    ]
  ];
  public static function config($params) {
    foreach ( $params as $k => $v ) self::$config[$k] = $v;
    self::load_libs();
    return self::route_path( endpoint() );
  }
  
  public static function load_libs($dir = null) {
    $dir = $dir ?: self::$config['root'] . '/../lib';
    foreach ( glob($dir . '/*.php') as $f ) {
      if ( is_dir($f) ) {
        self::load_libs($f);
      }
      else {
        require_once $f;
      }
    }
  }
  
  
  
  # execute component and return result
  public static function com() {
    $args = func_get_args();

    # laucnher & configuration handler
    if ( (count($args) == 1) && is_array($args[0]) && is_array($args[0]['config']) ) {
      self::config($args[0]['config']);
      
      if ( $_POST['com'] ) {
        header('Content-type: application/json');
        echo json_encode(['html' => phpy($_POST['com'])]);
      }
      else {
        header('Content-type: text/html;charset=utf8');
        echo phpy('/layout');
      }
    }
    
    # universal component renderer
    else {
      $data = $args[0];
      
      if ( !$data ) {
        $path = endpoint();
        if ( $path == '/' ) $path = '/default';
        else $path = rtrim($path, '/');
        
        $data = $path;
        $args[0] = $data;
      }
      
      if ( is_string($data) ) {
        if ( self::is_com_file($data) ) {
          $data = call_user_func_array('phpy::load_com_file', $args);
        }
        else if ( self::$config['routes'] ) {
          foreach ( self::$config['routes'] as $pattern => $path ) {
            if ( preg_match($pattern, $data) ) {
              if ( strpos($path, '?') ) {
                $get = [];
                parse_str(parse_url($path)['query'], $get);
                foreach ( $get as $k => $v ) $_GET[$k] = isset($_GET[$k]) ? $_GET[$k] : $v;
                $path = parse_url($path)['path'];
              }
              
              $data = $path;
              $args[0] = $data;
              
              if ( self::is_com_file($data) ) {
                $data = call_user_func_array('phpy::load_com_file', $args);
              }
              
              break;
            }
          }
        }
      }
      
      return self::render($data);
    }
  }
  
  
  
  # com files handlers
  public static function is_com_file($path) {
    return is_file(self::$config['root'] . '/../com/' . $path . '.php')
           ||
           is_file(self::$config['root'] . '/../com/' . $path . '/index.php');
  }
  public static function load_com_file($path, $context = []) {
    if ( is_array($context) ) {
      foreach ( $context as $k => $v ) {
        $$k = $v;
      }
    }
    
    return include(
      is_file(self::$config['root'] . '/../com/' . $path . '.php') ?
        self::$config['root'] . '/../com/' . $path . '.php' :
        self::$config['root'] . '/../com/' . $path . '/index.php'
    );
  }
  
  
  
  # renderer
  public static function render($data) {
    
    if ( is_array($data) ) {
      foreach ( $data as $k => $v ) {
        if ( method_exists('phpy', 'transform_' . $k) ) {
          $v = self::{"transform_{$k}"}($v);
          $response .= self::render_tags($v);
        }
        else {
          $response .= self::render_tags([$k => $v]);
        }
      }
    }
    else {
      $response = $data;
    }
    
    return $response;
  }
  
  # render transformers+
  public static function transform_layout($data) {
    $scripts = ['script' => ['attrs' => ['src' => '/client.js']]];
    if ( $data['scripts'] ) foreach ( $data['scripts'] as $url ) if ( $url ) {
      $scripts[] = ['script' => ['attrs' => ['src' => $url]]];
    }
    
    $styles = [ 'link' => ['attrs' => ['rel' => 'stylesheet', 'href' => '/ui.css']] ];
    if ( $data['styles'] ) foreach ( $data['styles'] as $url ) if ( $url ) {
      $styles[] = [ 'link' => ['attrs' => ['rel' => 'stylesheet', 'href' => $url]] ];
    }
    
    return [
      'html' => [
        'head' => [
          'title' => $data['title'],
          '<meta name="viewport" content="width=device-width, initial-scale=1">',
          $styles,
          [ 'link' => ['attrs' => ['rel' => "icon", 'href' => "/img/favicon.ico"]] ]
        ],
        'body' => ['html' => $data['html'], 'attrs' => $data['attrs']],
        $scripts
      ]
    ];
  }
  
  public static function transform_jsa($data) {
    $attrs = $data['attrs'] ?: [];
    
    foreach ( $data as $handler => $text ) {
      if ( $handler != 'attrs' ) {
        $attrs['onclick'] = $handler . '.call(this)';
        $data['html'] = $text;
        unset($data[$handler]);
      }
    }
    
    $attrs['href'] = 'javascript:;';
    
    return [
      'a' => [
        'html' => $data['html'],
        'attrs' => $attrs,
      ]
    ];
  }
  
  # universal tag renderer
  public static function render_tags($array) {
    $html = '';
    
    if ( !is_array($array) ) {
      return $array;
    }
    
    foreach ( $array as $tag => $inner ) {
      if ( is_numeric($tag) ) {
        $html .= self::render_tags($inner);
      }
      else {
        
        if ( method_exists('phpy', 'transform_' . $tag) ) {
          $inner = self::{"transform_{$tag}"}($inner);
          $html .= self::render_tags($inner);
        }
        else{
          $attrs = '';
          
          if ( strpos($tag, '.') !== false ) {
            if ( !is_array($inner) ) {
              $inner = ['html' => $inner];
            }
            
            $inner['attrs']['class'] = ($inner['attrs']['class'] ? $inner['attrs']['class'] . ' ' : '') .
                                       explode('.', $tag)[1];
            $tag = explode('.', $tag)[0] ?: 'div';
          }
          
          if ( strpos($tag, '#') !== false ) {
            if ( !is_array($inner) ) {
              $inner = ['html' => $inner];
            }
            
            $inner['attrs']['id'] = explode('#', $tag)[1];
            $tag = explode('#', $tag)[0] ?: 'div';
          }
          
          if ( is_array($inner) && $inner['attrs'] ) {
            foreach ( $inner['attrs'] as $n => $v ) {
              $attrs .= ' ' . $n . '="' . htmlspecialchars($v) . '"';
            }
            unset($inner['attrs']);
          }
          
          $html .= '<' . $tag . ($attrs ?: '') . '>' . self::render_tags($inner) . '</' . $tag . '>';
        }
      }
    }
    
    return $html;
  }
  
  
  
  # automatic path handlers
  public static function route_path($path) {
    foreach ( self::$config['route'] as $pattern => $type ) {
      if ( preg_match('/' . str_replace('*', '.+', $pattern) . '/', $path) ) {
        echo self::{'route_' . $type}($path);
        exit;
      }
    }
  }
  
  # file collector
  private static function collect_files_content($ext, $parent = null) {
    $parent = $parent ?: self::$config['root'] . '/../com';
    $content = '';
    foreach ( glob($parent . '/*') as $f ) {
      if ( is_dir($f) ) {
        $content .= self::collect_files_content($ext, $f);
      }
      else if ( pathinfo($f, PATHINFO_EXTENSION) == $ext ) {
        $content .= file_get_contents($f);
      }
    }
    
    return $content;
  }
  
  # js compiler
  public static function route_js($path) {
    header('Content-type: text/javascript');
    header('Expires: ' . gmdate("D, d M Y H:i:s", time() + 60*60*24*30) . ' GMT');
    
    $js = file_get_contents(__DIR__ . '/phpy.js');
    $js .= self::collect_files_content('js');
    
    return $js;
  }
  
  # css compiler
  public static function route_css($path) {
    header('Content-type: text/css');
    header('Expires: ' . gmdate("D, d M Y H:i:s", time() + 60*60*24*30) . ' GMT');
    
    $css = file_get_contents(__DIR__ . '/phpy.css');
    $css .= self::collect_files_content('css');
    
    $less = self::collect_files_content('less');
    if ( $less ) {
      $source = '/tmp/' . md5($less) . '.less';
      $dest = '/tmp/' . md5($less) . '.css';
      
      if ( !is_file($dest) ) {
        file_put_contents($source, $less);
        exec("lessc {$source} {$dest}");
      }
      
      $css .= file_get_contents($dest);
    }
    
    return $css;
  }
}


# Utility functions and helpers



# Global framework wrapper
function phpy() {
  return call_user_func_array('phpy::com', func_get_args());
}

# return current endpoint (URI path)
function endpoint() {
  return parse_url($_SERVER['REQUEST_URI'])['path'];
}


# redirect to another url
function go($url) {
  die(header('Location: ' . $url));
}


# true if is this a first call for this flag
function am_i_first($flag) {
  static $calls = [];
  
  if ( !$calls[$flag] ) {
    $calls[$flag] = true;
    return true;
  }
  
  return false;
}


# wrapper for single regex
function match($regex, $string) {
  preg_match($regex, $string, $m);
  return $m;
}

# wrapper for multiple matches regex
function matches($regex, $string) {
  preg_match_all($regex, $string, $m);
  
  $res = [];
  if ( $m ) {
    foreach ( $m[0] as $i => $match ) {
      foreach ( $m as $j => $column ) {
        $res[$i][$j] = $m[$j][$i];
      }
    }
  }
  
  return $res;
}


# returns current url with query params substituted/added
function url($params = []) {
  $url = parse_url($_SERVER['REQUEST_URI']);
  parse_str($url['query'], $q);
  foreach ( $params as $k => $v ) $q[$k] = $v;
  $query = http_build_query($q);
  return $url['path'] . ($query ? '?' . $query : '' );
}


# short alias for safe text output
function h($txt) {
  return htmlspecialchars($txt);
}