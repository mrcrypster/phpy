/* PHPy [server] component execution & render */

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
  else if ( !(data instanceof FormData) ) {
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

    if ( r.headers.get('Xpub') ) {
      let events = JSON.parse(atob(r.headers.get('Xpub')));
      for ( let e in events ) {
        pub(e, events[e]);
      }
    }
    
    return r.json();
  }).then(function(r) {
    for ( let k in r ) {
      qs(k, (e) => e.innerHTML = r[k]);
    }

    if ( typeof(callback) != 'undefined' ) {
      callback.apply(this, [r]);
    }
  });
}

// Query selector with callback
function qs(selector, callback) {
  let found = document.querySelectorAll(selector);

  if ( callback ) {
    found.forEach(function(el) { callback.apply(el, [el]); });
  }

  return found;
}


// Attach events using query selector
function on(selector, event, callback) {
  qs(selector).forEach(function(el) {
    el.addEventListener(event, callback);
  });
}
/* Pub/sub component */

let pubsub = {}

function pub(event, data) {
  if ( pubsub[event] ) {
    pubsub[event].forEach(function(cb) {
      cb(data);
    });
  }
}

function sub(event, callback) {
  if ( !pubsub[event] ) {
    pubsub[event] = [];
  }

  pubsub[event].push(callback);
}
