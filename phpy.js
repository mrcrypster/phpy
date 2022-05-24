// Universal com launcher
function phpy(com, data, callback) {
  data = data || {};
  if ( typeof data != 'object' ) {
    data = {value: data};
  }
  
  if ( data instanceof HTMLFormElement ) {
    data = new FormData(data);
  }
  else {
    data = new URLSearchParams(data);
  }
  
  fetch(com, {
    method: 'post',
    body: data
  }).then(function(r) {
    /*if ( r.headers.get('Xlocation') ) {
      location = r.headers.get('Xlocation');
      return;
    }*/
    
    return r.json();
  }).then(function(r) {
    for ( let k in r ) {
      qs(k, (e) => e.innerHTML = r[k]);
    }
  });
}


// Short query selector
function qs(selector, callback) {
  document.querySelectorAll(selector).forEach(function(el) {
    callback.apply(el, [el]);
  });
}