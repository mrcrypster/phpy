// --- JS minimalistic utilities for PHPy



// Execute server-side component by path (and data), write response into selector
var fetch_controllers = {}
function com(selector, com_path, data, callback) {
  if ( fetch_controllers[selector] ) {
    fetch_controllers[selector].abort();
  }
  
  fetch_controllers[selector] = new AbortController();

  data = data || {};
  if ( typeof data != 'object' ) {
    data = {value: data};
  }
  
  add_cls(selector, 'loading');
  
  if ( data instanceof HTMLFormElement ) {
    data = new FormData(data);
    data.append('com', com_path);
  }
  else {
    data.com = com_path;
    data = new URLSearchParams(data);
  }
  
  fetch('/', {
    signal: fetch_controllers[selector].signal,
    method: 'post',
    body: data
  }).then(function(r) {
    return r.json();
  }).then(function(r) {
    remove_cls(selector, 'loading');
    html(selector, r.html);
    callback(r);
  }).catch(function(e) {
    console.log(e);
  });
}



// --- DOM utilities ---

// query selector all wrapper
function qs(selector, parent) {
  parent = parent || document;
  return parent.querySelectorAll(selector);
}

// set html by selector
function html(selector, html) {
  qs(selector).forEach(function(el) { el.innerHTML = html; });
}

// set text by selector
function text(selector, html) {
  qs(selector).forEach(function(el) { el.innerText = html; });
}

// add class by selector
function add_cls(selector, classes) {
  qs(selector).forEach(function(el) {
    if ( typeof classes != 'array' ) classes = [classes];
    
    classes.forEach(function(cls) {
      el.classList.add(cls);
    })
  });
}

// re,pve class by selector
function remove_cls(selector, classes) {
  qs(selector).forEach(function(el) {
    if ( typeof classes != 'array' ) classes = [classes];
    
    classes.forEach(function(cls) {
      el.classList.remove(cls);
    })
  });
}

// live listener
function on(selector, event, callback) {
  document.addEventListener(event, e => {
    if ( (typeof selector == 'string') && e.target.closest && e.target.closest(selector) ) {
      callback.call(e.target.closest(selector), e);
    }
    else if ( selector == document ) {
      callback.call(document, e);
    }
  });
}

// stop propagation and prevent default handlers on event
function stop_event(e) {
  e.stopPropagation();
  e.preventDefault();
}

// Trigger event on element or selector
function trigger(who, what) {
  var els = typeof who == 'string' ? qs(selector) : [who];
  console.log(els[0].dispatchEvent(new Event('mouseover')));
  els.forEach(el => el.dispatchEvent(new Event(what)));
}

function l(message) {
  return console.log(message);
}