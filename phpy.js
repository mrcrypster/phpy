// Universal com launcher
function phpy(com, data, callback) {
  data = data || {};
  if ( typeof data != 'object' ) {
    data = {value: data};
  }

  if ( typeof this.dataset == 'object' ) {
    for ( var k in this.dataset ) {
      data[k] = this.dataset[k];
    }
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
    if ( r.headers.get('Xlocation') ) {
      location = r.headers.get('Xlocation');
      return;
    }
    
    return r.json();
  }).then(function(r) {
    for ( let k in r ) {
      qs(k).forEach((e) => e.innerHTML = r[k]);
    }

    if ( typeof(callback) != 'undefined' ) {
      callback.apply(this, [r]);
    }
  });
}


// Short query selector
function qs(selector, callback) {
  let found = document.querySelectorAll(selector);

  if ( callback ) {
    found.forEach(function(el) { callback.apply(el, [el]); });
  }

  return found;
}
