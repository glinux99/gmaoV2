import{s as g}from"./index-l1gA3O77.js";import{s as p,a as d,b}from"./index-CVfOes_D.js";import{b as I}from"./index-ZmKqUJz2.js";import{R as O,s as j}from"./index-DEQ-YT_x.js";import{B as P,U as S,V as x,o as c,e as m,f as B,q as f,j as z,D as t,E as r,l as i,X as C,p as h,ad as D,Y as T}from"./app-qY9iQY0c.js";var E=function(n){var e=n.dt;return`
.p-message {
    border-radius: `.concat(e("message.border.radius"),`;
    outline-width: `).concat(e("message.border.width"),`;
    outline-style: solid;
}

.p-message-content {
    display: flex;
    align-items: center;
    padding: `).concat(e("message.content.padding"),`;
    gap: `).concat(e("message.content.gap"),`;
    height: 100%;
}

.p-message-icon {
    flex-shrink: 0;
}

.p-message-close-button {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin: 0 0 0 auto;
    overflow: hidden;
    position: relative;
    width: `).concat(e("message.close.button.width"),`;
    height: `).concat(e("message.close.button.height"),`;
    border-radius: `).concat(e("message.close.button.border.radius"),`;
    background: transparent;
    transition: background `).concat(e("message.transition.duration"),", color ").concat(e("message.transition.duration"),", outline-color ").concat(e("message.transition.duration"),", box-shadow ").concat(e("message.transition.duration"),`, opacity 0.3s;
    outline-color: transparent;
    color: inherit;
    padding: 0;
    border: none;
    cursor: pointer;
    user-select: none;
}

.p-message-close-icon {
    font-size: `).concat(e("message.close.icon.size"),`;
    width: `).concat(e("message.close.icon.size"),`;
    height: `).concat(e("message.close.icon.size"),`;
}

.p-message-close-button:focus-visible {
    outline-width: `).concat(e("message.close.button.focus.ring.width"),`;
    outline-style: `).concat(e("message.close.button.focus.ring.style"),`;
    outline-offset: `).concat(e("message.close.button.focus.ring.offset"),`;
}

.p-message-info {
    background: `).concat(e("message.info.background"),`;
    outline-color: `).concat(e("message.info.border.color"),`;
    color: `).concat(e("message.info.color"),`;
    box-shadow: `).concat(e("message.info.shadow"),`;
}

.p-message-info .p-message-close-button:focus-visible {
    outline-color: `).concat(e("message.info.close.button.focus.ring.color"),`;
    box-shadow: `).concat(e("message.info.close.button.focus.ring.shadow"),`;
}

.p-message-info .p-message-close-button:hover {
    background: `).concat(e("message.info.close.button.hover.background"),`;
}

.p-message-success {
    background: `).concat(e("message.success.background"),`;
    outline-color: `).concat(e("message.success.border.color"),`;
    color: `).concat(e("message.success.color"),`;
    box-shadow: `).concat(e("message.success.shadow"),`;
}

.p-message-success .p-message-close-button:focus-visible {
    outline-color: `).concat(e("message.success.close.button.focus.ring.color"),`;
    box-shadow: `).concat(e("message.success.close.button.focus.ring.shadow"),`;
}

.p-message-success .p-message-close-button:hover {
    background: `).concat(e("message.success.close.button.hover.background"),`;
}

.p-message-warn {
    background: `).concat(e("message.warn.background"),`;
    outline-color: `).concat(e("message.warn.border.color"),`;
    color: `).concat(e("message.warn.color"),`;
    box-shadow: `).concat(e("message.warn.shadow"),`;
}

.p-message-warn .p-message-close-button:focus-visible {
    outline-color: `).concat(e("message.warn.close.button.focus.ring.color"),`;
    box-shadow: `).concat(e("message.warn.close.button.focus.ring.shadow"),`;
}

.p-message-warn .p-message-close-button:hover {
    background: `).concat(e("message.warn.close.button.hover.background"),`;
}

.p-message-error {
    background: `).concat(e("message.error.background"),`;
    outline-color: `).concat(e("message.error.border.color"),`;
    color: `).concat(e("message.error.color"),`;
    box-shadow: `).concat(e("message.error.shadow"),`;
}

.p-message-error .p-message-close-button:focus-visible {
    outline-color: `).concat(e("message.error.close.button.focus.ring.color"),`;
    box-shadow: `).concat(e("message.error.close.button.focus.ring.shadow"),`;
}

.p-message-error .p-message-close-button:hover {
    background: `).concat(e("message.error.close.button.hover.background"),`;
}

.p-message-secondary {
    background: `).concat(e("message.secondary.background"),`;
    outline-color: `).concat(e("message.secondary.border.color"),`;
    color: `).concat(e("message.secondary.color"),`;
    box-shadow: `).concat(e("message.secondary.shadow"),`;
}

.p-message-secondary .p-message-close-button:focus-visible {
    outline-color: `).concat(e("message.secondary.close.button.focus.ring.color"),`;
    box-shadow: `).concat(e("message.secondary.close.button.focus.ring.shadow"),`;
}

.p-message-secondary .p-message-close-button:hover {
    background: `).concat(e("message.secondary.close.button.hover.background"),`;
}

.p-message-contrast {
    background: `).concat(e("message.contrast.background"),`;
    outline-color: `).concat(e("message.contrast.border.color"),`;
    color: `).concat(e("message.contrast.color"),`;
    box-shadow: `).concat(e("message.contrast.shadow"),`;
}

.p-message-contrast .p-message-close-button:focus-visible {
    outline-color: `).concat(e("message.contrast.close.button.focus.ring.color"),`;
    box-shadow: `).concat(e("message.contrast.close.button.focus.ring.shadow"),`;
}

.p-message-contrast .p-message-close-button:hover {
    background: `).concat(e("message.contrast.close.button.hover.background"),`;
}

.p-message-text {
    font-size: `).concat(e("message.text.font.size"),`;
    font-weight: `).concat(e("message.text.font.weight"),`;
}

.p-message-icon {
    font-size: `).concat(e("message.icon.size"),`;
    width: `).concat(e("message.icon.size"),`;
    height: `).concat(e("message.icon.size"),`;
}

.p-message-enter-from {
    opacity: 0;
}

.p-message-enter-active {
    transition: opacity 0.3s;
}

.p-message.p-message-leave-from {
    max-height: 1000px;
}

.p-message.p-message-leave-to {
    max-height: 0;
    opacity: 0;
    margin: 0;
}

.p-message-leave-active {
    overflow: hidden;
    transition: max-height 0.45s cubic-bezier(0, 1, 0, 1), opacity 0.3s, margin 0.3s;
}

.p-message-leave-active .p-message-close-button {
    opacity: 0;
}
`)},A={root:function(n){var e=n.props;return"p-message p-component p-message-"+e.severity},content:"p-message-content",icon:"p-message-icon",text:"p-message-text",closeButton:"p-message-close-button",closeIcon:"p-message-close-icon"},M=P.extend({name:"message",theme:E,classes:A}),N={name:"BaseMessage",extends:j,props:{severity:{type:String,default:"info"},closable:{type:Boolean,default:!1},life:{type:Number,default:null},icon:{type:String,default:void 0},closeIcon:{type:String,default:void 0},closeButtonProps:{type:null,default:null}},style:M,provide:function(){return{$pcMessage:this,$parentInstance:this}}},L={name:"Message",extends:N,inheritAttrs:!1,emits:["close","life-end"],timeout:null,data:function(){return{visible:!0}},mounted:function(){var n=this;this.life&&setTimeout(function(){n.visible=!1,n.$emit("life-end")},this.life)},methods:{close:function(n){this.visible=!1,this.$emit("close",n)}},computed:{iconComponent:function(){return{info:p,success:g,warn:d,error:b}[this.severity]},closeAriaLabel:function(){return this.$primevue.config.locale.aria?this.$primevue.config.locale.aria.close:void 0}},directives:{ripple:O},components:{TimesIcon:I,InfoCircleIcon:p,CheckIcon:g,ExclamationTriangleIcon:d,TimesCircleIcon:b}};function a(o){"@babel/helpers - typeof";return a=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(n){return typeof n}:function(n){return n&&typeof Symbol=="function"&&n.constructor===Symbol&&n!==Symbol.prototype?"symbol":typeof n},a(o)}function v(o,n){var e=Object.keys(o);if(Object.getOwnPropertySymbols){var s=Object.getOwnPropertySymbols(o);n&&(s=s.filter(function(l){return Object.getOwnPropertyDescriptor(o,l).enumerable})),e.push.apply(e,s)}return e}function y(o){for(var n=1;n<arguments.length;n++){var e=arguments[n]!=null?arguments[n]:{};n%2?v(Object(e),!0).forEach(function(s){V(o,s,e[s])}):Object.getOwnPropertyDescriptors?Object.defineProperties(o,Object.getOwnPropertyDescriptors(e)):v(Object(e)).forEach(function(s){Object.defineProperty(o,s,Object.getOwnPropertyDescriptor(e,s))})}return o}function V(o,n,e){return(n=K(n))in o?Object.defineProperty(o,n,{value:e,enumerable:!0,configurable:!0,writable:!0}):o[n]=e,o}function K(o){var n=R(o,"string");return a(n)=="symbol"?n:n+""}function R(o,n){if(a(o)!="object"||!o)return o;var e=o[Symbol.toPrimitive];if(e!==void 0){var s=e.call(o,n||"default");if(a(s)!="object")return s;throw new TypeError("@@toPrimitive must return a primitive value.")}return(n==="string"?String:Number)(o)}var q=["aria-label"];function U(o,n,e,s,l,u){var w=S("TimesIcon"),k=x("ripple");return c(),m(T,t({name:"p-message",appear:""},o.ptmi("transition")),{default:B(function(){return[f(z("div",t({class:o.cx("root"),role:"alert","aria-live":"assertive","aria-atomic":"true"},o.ptm("root")),[o.$slots.container?r(o.$slots,"container",{key:0,closeCallback:u.close}):(c(),i("div",t({key:1,class:o.cx("content")},o.ptm("content")),[r(o.$slots,"icon",{class:"p-message-icon"},function(){return[(c(),m(C(o.icon?"span":null),t({class:[o.cx("icon"),o.icon]},o.ptm("icon")),null,16,["class"]))]}),o.$slots.default?(c(),i("div",t({key:0,class:["p-message-text",o.cx("text")]},o.ptm("text")),[r(o.$slots,"default")],16)):h("",!0),o.closable?f((c(),i("button",t({key:1,class:o.cx("closeButton"),"aria-label":u.closeAriaLabel,type:"button",onClick:n[0]||(n[0]=function($){return u.close($)})},y(y({},o.closeButtonProps),o.ptm("closeButton"))),[r(o.$slots,"closeicon",{},function(){return[o.closeIcon?(c(),i("i",t({key:0,class:[o.cx("closeIcon"),o.closeIcon]},o.ptm("closeIcon")),null,16)):(c(),m(w,t({key:1,class:[o.cx("closeIcon"),o.closeIcon]},o.ptm("closeIcon")),null,16,["class"]))]})],16,q)),[[k]]):h("",!0)],16))],16),[[D,l.visible]])]}),_:3},16)}L.render=U;export{L as s};
