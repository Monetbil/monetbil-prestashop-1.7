!function(e){var t=new Object,o={init:function(s){t=e.extend({},this,o),t.searching=!1,t.o=new Object;var n={overlaySelector:".md-overlay",closeSelector:".md-close",classAddAfterOpen:"md-show",modalAttr:"data-modal",perspectiveClass:"md-perspective",perspectiveSetClass:"md-setperspective",afterOpen:function(e,t){},afterClose:function(e,t){}};t.o=e.extend({},n,s),t.n=new Object;var r=e(t.o.overlaySelector);e(this).click(function(){function o(o){e(n).removeClass(t.o.classAddAfterOpen),n.css({perspective:"1300px"}),o&&e(document.documentElement).removeClass(t.o.perspectiveClass)}function s(){o(e(i).hasClass(t.o.perspectiveSetClass))}var n=e("#"+e(this).attr(t.o.modalAttr)),c=e(t.o.closeSelector,n),i=e(this);e(n).addClass(t.o.classAddAfterOpen),e(r).on("click",function(){s(),t.afterClose(i,n),e(r).off("click")}),e(i).hasClass(t.o.perspectiveSetClass)&&setTimeout(function(){e(document.documentElement).addClass(t.o.perspectiveClass)},25),t.afterOpen(i,n),setTimeout(function(){n.css({perspective:"none"}),n.height()%2!=0&&n.css({height:n.height()+1})},500),e(c).on("click",function(e){e.stopPropagation(),s(),t.afterClose(i,n)})})},afterOpen:function(e,o){t.o.afterOpen(e,o)},afterClose:function(e,o){t.o.afterClose(e,o)}};e.fn.modalEffects=function(t){return o[t]?o[t].apply(this,Array.prototype.slice.call(arguments,1)):"object"!=typeof t&&t?void e.error("Method "+t+" does not exist on jQuery.modalEffects"):o.init.apply(this,arguments)}}(jQuery),function(e){e.fn.niftyModal=function(t){var o={overlaySelector:".md-overlay",closeSelector:".md-close",classAddAfterOpen:"md-show",modalAttr:"data-modal",perspectiveClass:"md-perspective",perspectiveSetClass:"md-setperspective",afterOpen:function(e){},afterClose:function(e){}},s={},n={init:function(t){return this.each(function(){s=e.extend({},o,t);var n=e(this);r.showModal(n)})},toggle:function(t){return this.each(function(){s=e.extend({},o,t);var n=e(this);n.hasClass(s.classAddAfterOpen)?r.removeModal(n):r.showModal(n)})},show:function(t){return s=e.extend({},o,t),this.each(function(){r.showModal(e(this))})},hide:function(t){return s=e.extend({},o,t),this.each(function(){r.removeModal(e(this))})}},r={removeModal:function(e){e.removeClass(s.classAddAfterOpen),e.css({perspective:"1300px"}),e.trigger("hide")},showModal:function(t){var o=e(s.overlaySelector),n=e(s.closeSelector,t);t.addClass(s.classAddAfterOpen),o.on("click",function(e){var n=s.afterClose(t,e);(void 0===n||0!=n)&&(r.removeModal(t),o.off("click"))}),s.afterOpen(t),setTimeout(function(){t.css({perspective:"none"}),t.height()%2!=0&&t.css({height:modal.height()+1})},500),n.on("click",function(e){var n=s.afterClose(t,e);(void 0===n||0!=n)&&(r.removeModal(t),o.off("click")),e.stopPropagation()}),t.trigger("show")}};return n[t]?n[t].apply(this,Array.prototype.slice.call(arguments,1)):"object"!=typeof t&&t?void e.error('Method "'+t+'" does not exist in niftyModal plugin!'):n.init.apply(this,arguments)}}(jQuery);$(document).ready(function(){$(".md-trigger").modalEffects();});