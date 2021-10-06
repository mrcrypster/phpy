<?php

# simple session get/set with lazy-load

function session($key, $value = null) {
  static $started = false;
  if ( !$started ) {
    session_start();
  }
  
  if ( is_null($value) ) {
    return $_SESSION[$key];
  }
  else {
    return $_SESSION[$key] = $value;
  }
}