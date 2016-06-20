var EcomailFront = (function() {

  var _module = {

    init: function( config ) {
      var config = jQuery.extend( {
        ajaxUrl:                    null,
        cookieNameTrackStructEvent: null
      }, config );

      var $ = jQuery;

      $( document ).ajaxComplete( function() {
        processTrackStructEvent( config );
      } );

      processTrackStructEvent( config );
    }

  };

  var processTrackStructEvent = function( config ) {

    var v = readCookie( config.cookieNameTrackStructEvent );
    if( v ) {

      try {
        v = jQuery.parseJSON( v );
        if( v ) {
          console.log( 'ecotrack' );
          window.ecotrack( 'trackStructEvent', v.category, v.action, v.tag, v.property, v.value );
        }
      }
      catch( e ) {

      }

      eraseCookie( config.cookieNameTrackStructEvent );
    }
  };

  function createCookie( name, value, days ) {
    var expires;

    if( days ) {
      var date = new Date();
      date.setTime( date.getTime() + (days*24*60*60*1000) );
      expires = "; expires=" + date.toGMTString();
    }
    else {
      expires = "";
    }

    var path = baseUri.replace( window.location.origin, '' ).replace( /\/$/, '' );
    document.cookie = encodeURIComponent( name ) + "=" + encodeURIComponent( value ) + expires + "; path=" + path;
  }

  function readCookie( name ) {
    var nameEQ = name + "=";
    var ca = document.cookie.split( ';' );
    for( var i = 0; i < ca.length; i++ ) {
      var c = ca[i];
      while( c.charAt( 0 ) == ' ' ) c = c.substring( 1, c.length );
      if( c.indexOf( nameEQ ) == 0 ) return decodeURIComponent( c.substring( nameEQ.length, c.length ) );
    }
    return null;
  }

  function eraseCookie( name ) {
    createCookie( name, "", -1 );
  }

  return _module;

})();