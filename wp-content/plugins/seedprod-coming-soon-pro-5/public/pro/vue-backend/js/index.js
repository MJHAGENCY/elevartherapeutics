/*!
* Lettering.JS 0.6.1
*
* Copyright 2010, Dave Rupert http://daverupert.com
* Released under the WTFPL license
* http://sam.zoy.org/wtfpl/
*
* Thanks to Paul Irish - http://paulirish.com - for the feedback.
*
* Date: Mon Sep 20 17:14:00 2010 -0600
*/
(function(t){function e(e,s,o,i){var n=e.text().split(s),a="";n.length&&(t(n).each(function(t,e){a+='<span class="'+o+(t+1)+'">'+e+"</span>"+i}),e.empty().append(a))}var s={init:function(){return this.each(function(){e(t(this),"","char","")})},words:function(){return this.each(function(){e(t(this)," ","word"," ")})},lines:function(){return this.each(function(){var s="eefec303079ad17405c889e092e105b0";e(t(this).children("br").replaceWith(s).end(),s,"line","")})}};t.fn.lettering=function(e){return e&&s[e]?s[e].apply(this,[].slice.call(arguments,1)):"letters"!==e&&e?(t.error("Method "+e+" does not exist on jQuery.lettering"),this):s.init.apply(this,[].slice.call(arguments,0))}})(jQuery)}});