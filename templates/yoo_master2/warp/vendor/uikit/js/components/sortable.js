!function(t){var e;window.UIkit&&(e=t(UIkit)),"function"==typeof define&&define.amd&&define("uikit-sortable",["uikit"],function(){return e||t(UIkit)})}(function(t){"use strict";function e(e){e=t.$(e);do{if(e.data("sortable"))return e;e=t.$(e).parent()}while(e.length);return e}function o(t,e){var o=t.parentNode;if(e.parentNode!=o)return!1;for(var n=t.previousSibling;n&&9!==n.nodeType;){if(n===e)return!0;n=n.previousSibling}return!1}function n(t,e){var o=e;if(o==t)return null;for(;o;){if(o.parentNode===t)return o;if(o=o.parentNode,!o||!o.ownerDocument||11===o.nodeType)break}return null}function s(t){t.stopPropagation&&t.stopPropagation(),t.preventDefault&&t.preventDefault(),t.returnValue=!1}var a,r,i,l,d,h,u,p,c,g="ontouchstart"in window||window.DocumentTouch&&document instanceof DocumentTouch;return t.component("sortable",{defaults:{animation:150,threshold:10,childClass:"uk-sortable-item",placeholderClass:"uk-sortable-placeholder",overClass:"uk-sortable-over",draggingClass:"uk-sortable-dragged",dragMovingClass:"uk-sortable-moving",baseClass:"uk-sortable",noDragClass:"uk-sortable-nodrag",emptyClass:"uk-sortable-empty",dragCustomClass:"",handleClass:!1,group:!1,stop:function(){},start:function(){},change:function(){}},boot:function(){t.ready(function(e){t.$("[data-uk-sortable]",e).each(function(){var e=t.$(this);e.data("sortable")||t.sortable(e,t.Utils.options(e.attr("data-uk-sortable")))})}),t.$html.on("mousemove touchmove",function(e){if(u){var o=e.originalEvent.targetTouches?e.originalEvent.targetTouches[0]:e;(Math.abs(o.pageX-u.pos.x)>u.threshold||Math.abs(o.pageY-u.pos.y)>u.threshold)&&u.apply(o)}if(a){d||(d=!0,a.show(),a.$current.addClass(a.$sortable.options.placeholderClass),a.$sortable.element.children().addClass(a.$sortable.options.childClass),t.$html.addClass(a.$sortable.options.dragMovingClass));var n=a.data("mouse-offset"),s=parseInt(e.originalEvent.pageX,10)+n.left,r=parseInt(e.originalEvent.pageY,10)+n.top;if(a.css({left:s,top:r}),r+a.height()/3>document.body.offsetHeight)return;r<t.$win.scrollTop()?t.$win.scrollTop(t.$win.scrollTop()-Math.ceil(a.height()/3)):r+a.height()/3>window.innerHeight+t.$win.scrollTop()&&t.$win.scrollTop(t.$win.scrollTop()+Math.ceil(a.height()/3))}}),t.$html.on("mouseup touchend",function(t){if(u=h=!1,!r||!a)return void(r=a=null);var o=e(t.target),n=a.$sortable,s={type:t.type};o[0]&&n.dragDrop(s,n.element),n.dragEnd(s,n.element)})},init:function(){function e(){g?h.addEventListener("touchmove",v,!1):(h.addEventListener("mouseover",f,!1),h.addEventListener("mouseout",m,!1))}function o(){g?h.removeEventListener("touchmove",v,!1):(h.removeEventListener("mouseover",f,!1),h.removeEventListener("mouseout",m,!1))}function a(t){r&&d.dragMove(t,d)}function l(e){return function(o){var s,a,r;o&&(s=g&&o.touches&&o.touches[0]||{},a=s.target||o.target,g&&document.elementFromPoint&&(a=document.elementFromPoint(o.pageX-document.body.scrollLeft,o.pageY-document.body.scrollTop))),t.$(a).hasClass(d.options.childClass)?e.apply(a,[o]):a!==h&&(r=n(h,a),r&&e.apply(r,[o]))}}var d=this,h=this.element[0];p=[],this.checkEmptyList(),this.element.data("sortable-group",this.options.group?this.options.group:t.Utils.uid("sortable-group"));var u=l(function(e){var o=t.$(e.target),n=o.is("a[href]")?o:o.parents("a[href]");if(!o.is(":input"))return e.preventDefault(),!g&&n.length&&n.one("click",function(t){t.preventDefault()}).one("mouseup",function(){c||n.trigger("click")}),d.dragStart(e,this)}),f=(l(function(t){return r?(t.preventDefault&&t.preventDefault(),!1):!0}),l(t.Utils.debounce(function(t){return d.dragEnter(t,this)}),40)),m=l(function(){var e=d.dragenterData(this);d.dragenterData(this,e-1),d.dragenterData(this)||(t.$(this).removeClass(d.options.overClass),d.dragenterData(this,!1))}),v=l(function(t){return r&&r!==this&&i!==this?(d.element.children().removeClass(d.options.overClass),i=this,d.moveElementNextTo(r,this),s(t)):!0});this.addDragHandlers=e,this.removeDragHandlers=o,window.addEventListener(g?"touchmove":"mousemove",a,!1),h.addEventListener(g?"touchstart":"mousedown",u,!1)},dragStart:function(e,o){c=!1,d=!1,l=!1;var n=this,s=t.$(e.target);if(g||2!=e.button){if(n.options.handleClass){var i=s.hasClass(n.options.handleClass)?s:s.closest("."+n.options.handleClass,n.element);if(!i.length)return}if(!s.is("."+n.options.noDragClass)&&!s.closest("."+n.options._noDragClass).length&&!s.is(":input")){r=o,a&&a.remove();var h=t.$(r),p=h.offset();u={pos:{x:e.pageX,y:e.pageY},threshold:n.options.threshold,apply:function(e){a=t.$('<div class="'+[n.options.draggingClass,n.options.dragCustomClass].join(" ")+'"></div>').css({display:"none",top:p.top,left:p.left,width:h.width(),height:h.height(),padding:h.css("padding")}).data({"mouse-offset":{left:p.left-parseInt(e.pageX,10),top:p.top-parseInt(e.pageY,10)},origin:n.element,index:h.index()}).append(h.html()).appendTo("body"),a.$current=h,a.$sortable=n,h.data({"start-list":h.parent(),"start-index":h.index(),"sortable-group":n.options.group}),n.addDragHandlers(),n.options.start(this,r),n.trigger("start.uk.sortable",[n,r]),c=!0,u=!1}}}}},dragMove:function(e){var o,n=t.$(document.elementFromPoint(e.pageX-document.body.scrollLeft,e.pageY-(window.pageYOffset||document.documentElement.scrollTop))),s=n.closest("."+this.options.baseClass),a=s.data("sortable-group"),i=t.$(r),l=i.parent(),d=i.data("sortable-group");s[0]!==l[0]&&void 0!==d&&a===d&&(s.data("sortable").addDragHandlers(),p.push(s),s.children().addClass(this.options.childClass),s.children().length>0?(o=n.closest("."+this.options.childClass),o.length?o.before(i):s.append(i)):n.append(i),UIkit.$doc.trigger("mouseover")),this.checkEmptyList(),this.checkEmptyList(l)},dragEnter:function(e,o){if(!r||r===o)return!0;var n=this.dragenterData(o);return this.dragenterData(o,n+1),0===n&&(t.$(o).addClass(this.options.overClass),this.moveElementNextTo(r,o)),!1},dragEnd:function(e,o){var n=this;r&&(this.options.stop(o),this.trigger("stop.uk.sortable",[this])),r=null,i=null,p.push(this.element),p.forEach(function(e){t.$(e).children().each(function(){1===this.nodeType&&(t.$(this).removeClass(n.options.overClass).removeClass(n.options.placeholderClass).removeClass(n.options.childClass),n.dragenterData(this,!1))})}),p=[],t.$html.removeClass(this.options.dragMovingClass),this.removeDragHandlers(),a&&(a.remove(),a=null)},dragDrop:function(t){"drop"===t.type&&(t.stopPropagation&&t.stopPropagation(),t.preventDefault&&t.preventDefault()),this.triggerChangeEvents()},triggerChangeEvents:function(){if(r){var e=t.$(r),o=a.data("origin"),n=e.closest("."+this.options.baseClass),s=[],i=t.$(r);o[0]===n[0]&&a.data("index")!=e.index()?s.push({sortable:this,mode:"moved"}):o[0]!=n[0]&&s.push({sortable:t.$(n).data("sortable"),mode:"added"},{sortable:t.$(o).data("sortable"),mode:"removed"}),s.forEach(function(t){t.sortable.element.trigger("change.uk.sortable",[t.sortable,i,t.mode])})}},dragenterData:function(e,o){return e=t.$(e),1==arguments.length?parseInt(e.data("child-dragenter"),10)||0:void(o?e.data("child-dragenter",Math.max(0,o)):e.removeData("child-dragenter"))},moveElementNextTo:function(e,n){l=!0;var s=this,a=t.$(e).parent().css("min-height",""),r=o(e,n)?n:n.nextSibling,i=a.children(),d=i.length;return s.options.animation?(a.css("min-height",a.height()),i.stop().each(function(){var e=t.$(this),o=e.position();o.width=e.width(),e.data("offset-before",o)}),n.parentNode.insertBefore(e,r),t.Utils.checkDisplay(s.element.parent()),i=a.children().each(function(){var e=t.$(this);e.data("offset-after",e.position())}).each(function(){var e=t.$(this),o=e.data("offset-before");e.css({position:"absolute",top:o.top,left:o.left,"min-width":o.width})}),void i.each(function(){var e=t.$(this),o=(e.data("offset-before"),e.data("offset-after"));e.css("pointer-events","none").width(),setTimeout(function(){e.animate({top:o.top,left:o.left},s.options.animation,function(){e.css({position:"",top:"",left:"","min-width":"","pointer-events":""}).removeClass(s.options.overClass).removeData("child-dragenter"),d--,d||(a.css("min-height",""),t.Utils.checkDisplay(s.element.parent()))})},0)})):(n.parentNode.insertBefore(e,r),void t.Utils.checkDisplay(s.element.parent()))},serialize:function(){var e,o,n=[];return this.element.children().each(function(s,a){e={};for(var r=0;r<a.attributes.length;r++)o=a.attributes[r],0===o.name.indexOf("data-")&&(e[o.name.substr(5)]=t.Utils.str2json(o.value));n.push(e)}),n},checkEmptyList:function(e){e=e?t.$(e):this.element,this.options.emptyClass&&e[e.children().length?"removeClass":"addClass"](this.options.emptyClass)}}),t.sortable});