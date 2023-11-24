<?php

if ( php_sapi_name() == "cli" ) {
  if ( isset($argv[1]) && $argv[1] == 'init' ) {
    $dir = isset($argv[2]) ? $argv[2] : getcwd();

    foreach ( ['/web', '/app', '/lib'] as $d ) {
      mkdir($dir . $d, 0755, true) ? print "Created {$dir}{$d} dir\n" : die("Failed to create {$dir}{$d} \n");
    }

    file_put_contents(
      $dir . '/boot.php',
      '<' . '?php' . "\n\n" .
      'require_once \'' . __FILE__ . '\';' . "\n" .
      'require_once __DIR__ . \'/lib/helpers.php\';' . "\n"
    );

    file_put_contents(
      $dir . '/web/index.php',
      '<' . '?php' . "\n\n" .
      'require_once __DIR__ . \'/../boot.php\';' . "\n" .
      'phpy::on(\'/css.css\', fn() => phpy::css());' . "\n" .
      'phpy::on(\'/js.js\', fn() => phpy::js());' . "\n\n" .
      'echo phpy([\'/\' => __DIR__]);' . "\n"
    );

    file_put_contents(
      $dir . '/app/layout.php',
      '<' . '?php return [\'html\' => [' . "\n" .
      '  \':v\' => 1,' . "\n" .
      '  \':title\' => \'PHPy2 App\',' . "\n" .
      '  \'div#content\' => phpy()' . "\n" .
      ']];'
    );

    file_put_contents(
      $dir . '/app/default.php',
      '<' . '?php return [\'h1\' => \'I am the PHPy2 app\'];'
    );

    file_put_contents(
      $dir . '/lib/helpers.php',
      '<' . '?php'
    );

    echo "\n";
    echo 'App files created, configure your Nginx now:' . "\n\n";
    echo '------' . "\n";
    echo 'server {' . "\n" .
         '  root ' . $dir . '/web;' . "\n" .
         '  index index.php;' . "\n" .
         '  ' . "\n" .
         '  server_name myapp;' . "\n" .
         '  location / {' . "\n" .
         '    try_files $uri /index.php?$args /index.php?$args;' . "\n" .
         '  }' . "\n" .
         '  ' . "\n" .
         '  location ~ \.php$ {' . "\n" .
         '    include snippets/fastcgi-php.conf;' . "\n" .
         '    fastcgi_pass unix:/run/php/php-fpm.sock;' . "\n" .
         '  }' . "\n" . '}';
    echo "\n" . '------' . "\n\n";
  }
}



/* Core engine */

class phpy {
  private $config = [ 'layout' => 'layout' ];
  public static $listeners = [];
  public static $events = [];

  public function __construct($config = []) {
    $this->config = array_merge($this->config ?: [], $config ?: []);
    if ( !isset($this->config['/']) ) {
      $this->config['/'] = getcwd();
    }
  }

  public function set($param, $value) {
    $this->config[$param] = $value;
  }

  public function get($param) {
    return $this->config[$param];
  }

  public static function instance($data = []) {
    static $phpy;

    if ( !$phpy ) {
      $phpy = new phpy($data);
    }

    return $phpy;
  }



  /* Global context */

  # Custom URI listeners
  public static function on($endpoint, $callback) {
    self::$listeners[$endpoint][] = $callback;
  }

  # Return current endpoint
  public static function endpoint() {
    return parse_url(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/')['path'];
  }

  # Publish event to client
  public static function pub($event, $data = true) {
    phpy::$events[$event] = $data;
  }



  # application launcher
  public function app() {
    foreach ( self::$listeners as $pattern => $handlers ) {
      if ( ($pattern == $this->endpoint()) || @preg_match($pattern, $this->endpoint()) ) {
        foreach ( $handlers as $cb ) {
          $continue = $cb($this);
        }
        
        if ( !$continue ) {
          return;
        }
      }
    }
    
    if ( $this->endpoint() == '/js.js' ) {
      header('Content-type: application/javascript');
      readfile(__DIR__ . '/phpy.js');
    }
    else if ( $this->endpoint() == '/css.css' ) {
      header('Content-type: text/css');
      readfile(__DIR__ . '/phpy.css');
    }
    else if ( isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST') ) {
      $data = $this->com_data( $this->endpoint() );

      foreach ( $data as $container => $tpl ) {
        $data[$container] = $this->render($tpl)[0];
      }

      header('Content-type: text/json');
      header('Xpub: ' . base64_encode(json_encode(phpy::$events)));

      return json_encode($data);
    }
    else {
      return $this->com_render( $this->config['layout'] );
    }
  }

  # com launcher
  public function com($com = null, $args = []) {
    if ( is_null($com) ) {
      $com = $this->endpoint();
    }

    return $this->com_render($com, $args);
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
  public function com_data($endpoint, $args = []) {
    $file = $this->com_file($endpoint);
    if ( $file ) {
      foreach ( $args as $k => $v ) {
        $$k = $v;
      }
      return include $file;
    }
    else {
      return [];
    }
  }

  # render com by endpoint
  public function com_render($endpoint, $args = []) {
    $tpl = $this->com_data($endpoint, $args);

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

    if ( strpos($tag, ':') ) {
      $params = explode(':', $tag);
      $tag = array_shift($params);
      foreach ( $params as $param ) {
        $attrs['default'][] = $param;
      }
    }

    if ( preg_match_all('/\.([^:# ]+)/', $tag, $mm) ) {
      foreach ( $mm[1] as $class ) {
        $classes[] = str_replace('.', ' ', $class);
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

    if ( !$tag ) {
      $tag = 'span';
    }

    if ( function_exists("phpy_post_render_{$tag}") ) {
      $custom_html = call_user_func_array("phpy_post_render_{$tag}", [&$html, &$attrs, $this]);
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
          $tag = preg_split('/(\.|:|#)/', $kk)[0];
          if ( function_exists("phpy_pre_render_{$tag}") ) {
            call_user_func_array("phpy_pre_render_{$tag}", [&$kk, &$tt, $this]);
          }

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



  /* Default routing handlers */
  public static function collect($dir, $exts) {
    $c = '';

    foreach (glob($dir . '/*') as $f ) {
      if ( is_dir($f) ) $c .= self::collect($f, $exts);
      else if ( in_array(pathinfo($f, PATHINFO_EXTENSION), $exts) ) $c .= "\n" . file_get_contents($f);
    }

    return $c;
  }

  public static function css() {
    header('Content-type: text/css');
    $js = file_get_contents(__DIR__ . '/phpy.css') . self::collect((self::instance()->get('/') . '/../app'), ['css']);
    echo $js;
  }

  public static function js() {
    header('Content-type: application/javascript');
    $js = file_get_contents(__DIR__ . '/phpy.js') . self::collect((self::instance()->get('/') . '/../app'), ['js']);
    echo $js;
  }
}



/* PHPy components */

# Universal components renderer
function phpy($data_or_com = null, $args = []) {
  static $app_loaded = false;

  if ( !$app_loaded ) {
    $app_loaded = true;
    return phpy::instance($data_or_com)->app();
  }

  if ( is_array($data_or_com) ) {
    return phpy::instance()->render($data_or_com, $args)[0];
  }

  return phpy::instance()->com($data_or_com, $args);
}

# files collector
function collect_files($extensions, $dir = null) {
  $content = '';

  $dir = isset($dir) ? $dir : dirname(phpy::instance()->get('/'));
  $dir .= '/*';

  if ( !is_array($extensions) ) {
    $extensions = [$extensions];
  }

  foreach ( glob($dir) as $f ) {
    if ( is_dir($f) ) {
      $content .= collect_files($extensions, $f);
    }
    else if ( in_array(pathinfo($f, PATHINFO_EXTENSION), $extensions) ) {
      $content .= file_get_contents($f) . "\n";
    }
  }

  return $content;
}

# get current endpoint
function endpoint() {
  return phpy::endpoint();
}

# pub/sub -> publish event
function pub($event, $data = true) {
  return phpy::pub($event, $data);
}



/* HTTP */

# redirect to specified URL (PHPy AJAX support included)
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

# generate url with query string based on current url
function url($params = [], $path = null) {
  $url = parse_url($_SERVER['REQUEST_URI']);
  parse_str($url['query'], $q);
  $query = http_build_query(array_merge($q?:[], $params));
  return ($path ?: $url['path']) . ($query ? '?' : '') . $query;
}



/* Utilities */

# escape string for safe output in html
function e($text) {
  return htmlspecialchars($text);
}

# safely returns specified $array $key or $default value
function akey($array, $key, $default = null) {
  return isset($array[$key]) ? $array[$key] : $default;
}

# returns number incremented by 1 for each new call
function nums($namespace = 'default') {
  static $counters = [];
  if ( !isset($counters[$namespace]) ) {
    return $counters[$namespace] = 1;
  }
  else {
    return ++$counters[$namespace];
  }
}




/* <html> */

function phpy_post_render_html(&$html, &$attrs) {
  $pub_events = [];
  if ( phpy::$events ) foreach ( phpy::$events as $event => $data ) {
    $pub_events[] = "pub(" . json_encode($event) . ", " . json_encode($data) . ");";
  }

  return '<html>' .
         '<head>' .
           '<title>' . akey($attrs, ':title') . '</title>' .
           '<link href="/css.css?' . akey($attrs, ':v') . '" rel="stylesheet">' .
            akey($attrs, ':head') .
         '</head>' .
         '<body>' . $html . '</body>' .
         '<script src="/js.js?' . akey($attrs, ':v') . '"></script>'.
         ($pub_events ? ('<script>' . implode(';', $pub_events) . '</script>') : '') .
         '</html>';
}



/* <a> */

function phpy_post_render_a(&$html, &$attrs) {
  if ( isset($attrs['default'][0]) ) {
    if ( strpos($attrs['default'][0], '(') ) {
      $attrs['href'] = 'javascript:' . $attrs['default'][0];
    }
    else {
      $attrs['href'] = $attrs['default'][0] ?: 'javascript:;';
    }
  }
}



/* <form> */

function phpy_post_render_form(&$html, &$attrs) {
  $after_callback = '';

  if ( isset($attrs['default'][1]) ) {
    $after_callback = ', ' . $attrs['default'][1];
  }

  if ( isset($attrs['default'][0]) ) {
    $attrs['action'] = $attrs['default'][0];
    $attrs['onsubmit'] = 'phpy.apply(this, [\'' . $attrs['action'] . '\', this' . $after_callback . ']); return false;';
  }
}



/* <select> */

function phpy_pre_render_select(&$key, &$tpl, $phpy) {
  $keys = explode(':', $key);
  $tpl = array_map(
    fn($v, $k) => ['option' => array_merge([':value' => $k, $v], $keys[2] == $k ? [':selected' => 'on'] : [])],
    array_values($tpl), array_keys($tpl)
  );
}

function phpy_post_render_select(&$html, &$attrs) {
  if ( isset($attrs['default'][0]) ) {
    $attrs['name'] = $attrs['default'][0];
  }
}



/* <datalist> */

function phpy_pre_render_datalist(&$key, &$tpl, $phpy) {
  $tpl = array_map(
    fn($v) => ['option' => [':value' => $v]],
    $tpl
  );
}

function phpy_post_render_datalist(&$html, &$attrs) {
  if ( isset($attrs['default'][0]) ) {
    $attrs['id'] = $attrs['default'][0];
  }
}



/* <dl> */

function phpy_pre_render_dl(&$key, &$tpl, $phpy) {
  $tpl = array_map(
    fn($v, $k) => ['dt' => $k, 'dd' => $v],
    array_values($tpl), array_keys($tpl)
  );
}




/* <button> */

function phpy_post_render_button(&$html, &$attrs) {
  $after_callback = '';

  if ( isset($attrs['default'][1]) ) {
    $after_callback = ', {}, ' . $attrs['default'][1];
  }
  
  $confirm = '';
  if ( isset($attrs['default'][2]) ) {
    $confirm = 'if ( confirm(\'' . e($attrs['default'][2]) . '\') ) ';
  }

  if ( isset($attrs['default'][0]) ) {
    if ( strpos($attrs['default'][0], '(') ) {
      $attrs['onclick'] = $attrs['default'][0];
    }
    else {
      $attrs['onclick'] = $confirm . 'phpy.apply(this, [\'' . $attrs['default'][0] . '\'' . $after_callback . '])';
    }
  }
  
  $attrs['type'] = isset($attrs['type']) ? $attrs['type'] : 'button';
}



/* <button type="submit"> */

function phpy_post_render_submit(&$html, &$attrs, $phpy) {
  $attrs['type'] = 'submit';
  $attrs_html = $phpy->tag_attrs($attrs);
  return "<button {$attrs_html}>{$html}</button>";
}



/* <input> */

function phpy_post_render_input(&$html, &$attrs) {
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



/* <input type="hidden"> */

function phpy_post_render_hidden(&$html, &$attrs, $phpy) {
  if ( $html && !isset($attrs['value']) ) {
    $attrs['value'] = $html;
    $html = '';
  }

  $attrs['type'] = 'hidden';

  if ( isset($attrs['default'][0]) ) {
    $attrs['name'] = $attrs['default'][0];
  }

  $attrs_html = $phpy->tag_attrs($attrs);
  return "<input {$attrs_html}/>";
}



/* <input type="file"> */

function phpy_post_render_file(&$html, &$attrs, $phpy) {
  $attrs['name'] = isset($attrs['default'][0]) ? $attrs['default'][0] : (isset($attrs['name']) ? $attrs['name'] : 'file');
  $attrs_html = $phpy->tag_attrs($attrs);
  
  return "<input type=\"file\" {$attrs_html}/>";
}



/* <input type="checkbox"> */

function phpy_post_render_check(&$html, &$attrs, $phpy) {
  $attrs['name'] = isset($attrs['default'][0]) ? $attrs['default'][0] : (isset($attrs['name']) ? $attrs['name'] : 'check');
  if ( $html || isset($attrs['default'][1]) ) {
    $attrs['checked'] = 1;
  }
  
  $attrs_html = $phpy->tag_attrs($attrs);
  
  return "<input type=\"checkbox\" {$attrs_html}/>";
}



/* <input type="radio"> */

function phpy_post_render_radio(&$html, &$attrs, $phpy) {
  $attrs['name'] = isset($attrs['default'][0]) ? $attrs['default'][0] : (isset($attrs['name']) ? $attrs['name'] : 'check');
  $attrs['value'] = (isset($attrs['value']) ? $attrs['value'] : $html);
  if ( isset($attrs['default'][1]) && $attrs['default'][1] ) {
    $attrs['checked'] = 1;
  }
  
  $attrs_html = $phpy->tag_attrs($attrs);
  
  return "<input type=\"radio\" {$attrs_html}/>";
}



/* <img> */

function phpy_post_render_img(&$html, &$attrs, $phpy) {
  $attrs['src'] = $html ?: (isset($attrs['default'][0]) ? $attrs['default'][0] : (isset($attrs['src']) ? $attrs['src'] : ''));
  $attrs_html = $phpy->tag_attrs($attrs);
  
  return "<img {$attrs_html}/>";
}



/* <video> */

function phpy_post_render_video(&$html, &$attrs, $phpy) {
  if ( !is_array($html) && !isset($attrs['src']) ) {
    $html = '<source src="' . $html . '">';
  }

  $attrs_html = $phpy->tag_attrs($attrs);
  
  return "<video {$attrs_html}>{$html}</video>";
}



/* <iframe> */

function phpy_post_render_iframe(&$html, &$attrs, $phpy) {
  $attrs['src'] = $html ?: (isset($attrs['default'][0]) ? $attrs['default'][0] : (isset($attrs['src']) ? $attrs['src'] : ''));
  $attrs_html = $phpy->tag_attrs($attrs);
  
  return "<iframe {$attrs_html}/>";
}



/* <progress> */

function phpy_post_render_progress(&$html, &$attrs, $phpy) {
  if ( isset($attrs['default'][0]) ) {
    $attrs['value'] = $attrs['default'][0];
  }
  
  $attrs['max'] = isset($attrs['default'][1]) ? $attrs['default'][1] : 100;
  $attrs_html = $phpy->tag_attrs($attrs);
  
  return "<progress {$attrs_html}>{$html}</progress>";
}



/* <textarea> */

function phpy_post_render_textarea(&$html, &$attrs) {
  if ( isset($attrs['default'][0]) ) {
    $attrs['name'] = $attrs['default'][0];
  }

  if ( isset($attrs['default'][1]) ) {
    $attrs['placeholder'] = $attrs['default'][1];
  }
}

