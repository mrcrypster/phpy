<?php

# Utility functions and helpers



# Global framework wrapper
function phpy() {
  return call_user_func_array('phpy::com', func_get_args());
}

# return current endpoint (URI path)
function endpoint() {
  return $_POST['com'] ?: parse_url($_SERVER['REQUEST_URI'])['path'];
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