<?php

/* Core engine */

class phpy {
  # init & config
  private $config = [];
  public static $listeners = [];

  public function __construct($config = []) {
    $this->config = $config;
  }

  public static function on($endpoint, $callback) {
    self::$listeners[$endpoint][] = $callback;
  }



  # global context
  public function endpoint() {
    return parse_url($_SERVER['REQUEST_URI'])['path'];
  }

  # files collector
  public function collect($extensions, $dir = null) {
    $content = '';
    if ( !$dir ) {
      $content .= $this->collect($extensions, __DIR__);
    }

    $dir = isset($dir) ? $dir : dirname($this->config['/']);
    $dir .= '/*';

    foreach ( glob($dir) as $f ) {
      if ( is_dir($f) ) {
        $content .= $this->collect($extensions, $f);
      }
      else if ( in_array(pathinfo($f, PATHINFO_EXTENSION), $extensions) ) {
        $content .= file_get_contents($f);
      }
    }

    return $content;
  }



  # application launcher
  public function app() {
    if ( isset(self::$listeners[$this->endpoint()]) ) {
      foreach ( self::$listeners[$this->endpoint()] as $cb ) {
        $cb($this);
      }
      return;
    }

    if ( $this->endpoint() == '/js.js' ) {
      header('Content-type: application/javascript');
      readfile(__DIR__ . '/phpy.js');
    }
    else if ( $this->endpoint() == '/css.css' ) {
      header('Content-type: text/css');
      readfile(__DIR__ . '/phpy.css');
    }
    else if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
      $data = $this->com_data( $this->endpoint() );

      foreach ( $data as $container => $tpl ) {
        $data[$container] = $this->render($tpl)[0];
      }

      header('Content-type: text/json');
      echo json_encode($data);
    }
    else {
      echo $this->com_render( isset($this->config['layout']) ?: 'layout' );
    }
  }

  # com launcher
  public function com($com = null) {
    if ( is_null($com) ) {
      $com = $this->endpoint();
    }

    return $this->com_render($com);
  }

  # get com file path
  public function com_file($endpoint) {
    $file = dirname($this->config['/']) . '/' .
           (isset($this->config['app']) ?: 'app') . '/' .
           $endpoint . '.php';

    if ( is_file($file) ) {
      return $file;
    }

    $file = dirname($this->config['/']) . '/' .
           (isset($this->config['app']) ?: 'app') . '/' .
           $endpoint . '/default.php';

    if ( is_file($file) ) {
      return $file;
    }
  }

  # get com data by endpoint
  public function com_data($endpoint) {
    $file = $this->com_file($endpoint);
    if ( $file ) {
      return include $file;
    }
    else {
      return [];
    }
  }

  # render com by endpoint
  public function com_render($endpoint, $context = []) {
    $tpl = $this->com_data($endpoint);

    # by default - render html
    if ( true ) {
      return $this->render($tpl)[0];
    }
  }



  # render tag from params
  public function tag($tag, $html, $attrs = []) {
    if ( is_numeric($tag) ) {
      return $html;
    }

    if ( preg_match_all('/\.([^:# ]+)/', $tag, $mm) ) {
      foreach ( $mm[1] as $class ) {
        $classes[] = $class;
        $tag = str_replace('.' . $class, '', $tag);
      }

      isset($attrs['class']) ? $attrs['class'] .= ' ' : $attrs['class'] = '';
      $attrs['class'] .= implode(' ', $classes);
    }

    if ( preg_match_all('/\#([^:. ]+)/', $tag, $mm) ) {
      foreach ( $mm[1] as $id ) {
        $tag = str_replace('#' . $id, '', $tag);
        $attrs['id'] = $id;
      }
    }

    if ( preg_match_all('/\:([^#.:]*)/', $tag, $mm) ) {
      foreach ( $mm[1] as $param ) {
        $tag = str_replace(':' . $param, '', $tag);
        $attrs['default'][] = $param;
      }
    }

    if ( !$tag ) {
      $tag = 'span';
    }

    if ( function_exists("phpy_pre_render_{$tag}") ) {
      $custom_html = call_user_func_array("phpy_pre_render_{$tag}", [&$html, &$attrs, $this]);
    }

    $attrs_html = $this->tag_attrs($attrs);

    return isset($custom_html) ? $custom_html :
           "<{$tag}{$attrs_html}>{$html}</{$tag}>";
  }

  # render tag attributes
  public function tag_attrs($attrs) {
    $pairs = [];
    foreach ( $attrs as $k => $v ) {
      $k = trim($k, ':');

      if ( $k == 'default' ) continue;
      if ( ($k == 'data') && is_array($v) ) {
        foreach ( $v as $data_k => $data_v ) {
          $pairs[] = 'data-' . $data_k . '="' . htmlspecialchars($data_v, ENT_COMPAT) .  '"';
        }
      }
      else {
        $pairs[] = $k . '="' . htmlspecialchars($v, ENT_COMPAT) .  '"';
      }
    }

    return $pairs ? ' ' . implode(' ', $pairs) : '';
  }

  # render html from phpy tpl
  public function render($t) {
    $html = '';
    $attrs = [];

    if ( is_array($t) ) {
      foreach ( $t as $kk => $tt ) {
        if ( substr($kk, 0, 1) == ':' ) {
          $attrs[$kk] = $tt;
        }
        else {
          list($in, $at) = $this->render($tt);
          $html .= $this->tag($kk, $in, $at);
        }
      }
    }
    else {
      $html = $t;
    }

    return [$html, $attrs];
  }
}



/* Universal component loader */

function phpy($data = null) {
  static $phpy;

  if ( !$phpy ) {
    $phpy = new phpy($data);
    return $phpy->app();
  }
  else {
    return $phpy->com($data);
  }
}



/* Tag preprocessors */

function phpy_pre_render_html(&$html, &$attrs) {
  return '<html>' .
         '<head>' .
           '<title>' . akey($attrs, ':title') . '</title>' .
           '<link href="/css.css?' . akey($attrs, ':v') . '" rel="stylesheet">' .
         '</head>' .
         '<body>' . $html . '</body>' .
         '<script src="/js.js?' . akey($attrs, ':v') . '"></script>'.
         '</html>';
}

function phpy_pre_render_a(&$html, &$attrs) {
  if ( isset($attrs['default'][0]) ) {
    if ( strpos($attrs['default'][0], '(') ) {
      $attrs['href'] = 'javascript:' . $attrs['default'][0];
    }
    else {
      $attrs['href'] = $attrs['default'][0] ?: 'javascript:;';
    }
  }
}

function phpy_pre_render_button(&$html, &$attrs) {
  if ( isset($attrs['default'][0]) ) {
    if ( strpos($attrs['default'][0], '(') ) {
      $attrs['onclick'] = $attrs['default'][0];
    }
    else {
      $attrs['onclick'] = 'phpy.apply(this, [\'' . $attrs['default'][0] . '\'])';
    }
  }
}

function phpy_pre_render_input(&$html, &$attrs) {
  if ( $html && !isset($attrs['value']) ) {
    $attrs['value'] = $html;
    $html = '';
  }

  if ( !isset($attrs['type']) ) {
    $attrs['type'] = 'text';
  }

  if ( isset($attrs['default'][0]) ) {
    $attrs['name'] = $attrs['default'][0];
  }

  if ( isset($attrs['default'][1]) ) {
    $attrs['placeholder'] = $attrs['default'][1];
  }
}

function phpy_pre_render_file(&$html, &$attrs, $phpy) {
  $attrs['name'] = isset($attrs['default'][0]) ? $attrs['default'][0] : (isset($attrs['name']) ? $attrs['name'] : 'file');
  $attrs_html = $phpy->tag_attrs($attrs);
  
  return "<input type=\"file\" {$attrs_html}/>";
}

function phpy_pre_render_textarea(&$html, &$attrs) {
  if ( isset($attrs['default'][0]) ) {
    $attrs['name'] = $attrs['default'][0];
  }

  if ( isset($attrs['default'][1]) ) {
    $attrs['placeholder'] = $attrs['default'][1];
  }
}

function phpy_pre_render_form(&$html, &$attrs) {
  if ( isset($attrs['default'][0]) ) {
    $attrs['action'] = $attrs['default'][0];
    $attrs['onsubmit'] = 'phpy.apply(this, [\'' . $attrs['action'] . '\', this]); return false;';
  }
}



/* Set of helpful utilities */
function akey($array, $key, $default = null) {
  return isset($array[$key]) ? $array[$key] : $default;
}

function redirect($url) {
  if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
    header('Xlocation: ' . $url);
    exit;
  }
  else {
    header('Location: ' . $url);
    exit;
  }
}

function e($text) {
  return htmlspecialchars($text);
}
