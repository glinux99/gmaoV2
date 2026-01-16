import{B as v,G as y,S as w,q as T,o as d,l as b,j as f,E as h,s as I,D as s,p as A,t as S,C as g,O as p,R as C,m as $,n as D,e as K,X as x,f as P}from"./app-BTm5MPCd.js";import{R as k,a as V}from"./index-DGDWVGXJ.js";var q=function(n){var t=n.dt;return`
.p-togglebutton {
    display: inline-flex;
    cursor: pointer;
    user-select: none;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
    color: `.concat(t("togglebutton.color"),`;
    background: `).concat(t("togglebutton.background"),`;
    border: 1px solid `).concat(t("togglebutton.border.color"),`;
    padding: `).concat(t("togglebutton.padding"),`;
    font-size: 1rem;
    font-family: inherit;
    font-feature-settings: inherit;
    transition: background `).concat(t("togglebutton.transition.duration"),", color ").concat(t("togglebutton.transition.duration"),", border-color ").concat(t("togglebutton.transition.duration"),`,
        outline-color `).concat(t("togglebutton.transition.duration"),", box-shadow ").concat(t("togglebutton.transition.duration"),`;
    border-radius: `).concat(t("togglebutton.border.radius"),`;
    outline-color: transparent;
    font-weight: `).concat(t("togglebutton.font.weight"),`;
}

.p-togglebutton-content {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: `).concat(t("togglebutton.gap"),`;
}

.p-togglebutton-label,
.p-togglebutton-icon {
    position: relative;
    transition: none;
}

.p-togglebutton::before {
    content: "";
    background: transparent;
    transition: background `).concat(t("togglebutton.transition.duration"),", color ").concat(t("togglebutton.transition.duration"),", border-color ").concat(t("togglebutton.transition.duration"),`,
            outline-color `).concat(t("togglebutton.transition.duration"),", box-shadow ").concat(t("togglebutton.transition.duration"),`;
    position: absolute;
    left: `).concat(t("togglebutton.content.left"),`;
    top: `).concat(t("togglebutton.content.top"),`;
    width: calc(100% - calc(2 *  `).concat(t("togglebutton.content.left"),`));
    height: calc(100% - calc(2 *  `).concat(t("togglebutton.content.top"),`));
    border-radius: `).concat(t("togglebutton.border.radius"),`;
}

.p-togglebutton.p-togglebutton-checked::before {
    background: `).concat(t("togglebutton.content.checked.background"),`;
    box-shadow: `).concat(t("togglebutton.content.checked.shadow"),`;
}

.p-togglebutton:not(:disabled):not(.p-togglebutton-checked):hover {
    background: `).concat(t("togglebutton.hover.background"),`;
    color: `).concat(t("togglebutton.hover.color"),`;
}

.p-togglebutton.p-togglebutton-checked {
    background: `).concat(t("togglebutton.checked.background"),`;
    border-color: `).concat(t("togglebutton.checked.border.color"),`;
    color: `).concat(t("togglebutton.checked.color"),`;
}

.p-togglebutton:focus-visible {
    box-shadow: `).concat(t("togglebutton.focus.ring.shadow"),`;
    outline: `).concat(t("togglebutton.focus.ring.width")," ").concat(t("togglebutton.focus.ring.style")," ").concat(t("togglebutton.focus.ring.color"),`;
    outline-offset: `).concat(t("togglebutton.focus.ring.offset"),`;
}

.p-togglebutton.p-invalid {
    border-color: `).concat(t("togglebutton.invalid.border.color"),`;
}

.p-togglebutton:disabled {
    opacity: 1;
    cursor: default;
    background: `).concat(t("togglebutton.disabled.background"),`;
    border-color: `).concat(t("togglebutton.disabled.border.color"),`;
    color: `).concat(t("togglebutton.disabled.color"),`;
}

.p-togglebutton-icon {
    color: `).concat(t("togglebutton.icon.color"),`;
}

.p-togglebutton:not(:disabled):not(.p-togglebutton-checked):hover .p-togglebutton-icon {
    color: `).concat(t("togglebutton.icon.hover.color"),`;
}

.p-togglebutton.p-togglebutton-checked .p-togglebutton-icon {
    color: `).concat(t("togglebutton.icon.checked.color"),`;
}

.p-togglebutton:disabled .p-togglebutton-icon {
    color: `).concat(t("togglebutton.icon.disabled.color"),`;
}
`)},E={root:function(n){var t=n.instance,l=n.props;return["p-togglebutton p-component",{"p-togglebutton-checked":t.active,"p-invalid":l.invalid}]},content:"p-togglebutton-content",icon:"p-togglebutton-icon",label:"p-togglebutton-label"},j=v.extend({name:"togglebutton",theme:q,classes:E}),R={name:"BaseToggleButton",extends:V,props:{modelValue:Boolean,onIcon:String,offIcon:String,onLabel:{type:String,default:"Yes"},offLabel:{type:String,default:"No"},iconPos:{type:String,default:"left"},invalid:{type:Boolean,default:!1},disabled:{type:Boolean,default:!1},readonly:{type:Boolean,default:!1},tabindex:{type:Number,default:null},ariaLabelledby:{type:String,default:null},ariaLabel:{type:String,default:null}},style:j,provide:function(){return{$pcToggleButton:this,$parentInstance:this}}},O={name:"ToggleButton",extends:R,inheritAttrs:!1,emits:["update:modelValue","change"],methods:{getPTOptions:function(n){var t=n==="root"?this.ptmi:this.ptm;return t(n,{context:{active:this.active,disabled:this.disabled}})},onChange:function(n){!this.disabled&&!this.readonly&&(this.$emit("update:modelValue",!this.modelValue),this.$emit("change",n))}},computed:{active:function(){return this.modelValue===!0},hasLabel:function(){return y(this.onLabel)&&y(this.offLabel)},label:function(){return this.hasLabel?this.modelValue?this.onLabel:this.offLabel:"&nbsp;"}},directives:{ripple:k}},N=["tabindex","disabled","aria-pressed","data-p-checked","data-p-disabled"];function z(e,n,t,l,i,o){var r=w("ripple");return T((d(),b("button",s({type:"button",class:e.cx("root"),tabindex:e.tabindex,disabled:e.disabled,"aria-pressed":e.modelValue,onClick:n[0]||(n[0]=function(){return o.onChange&&o.onChange.apply(o,arguments)})},o.getPTOptions("root"),{"data-p-checked":o.active,"data-p-disabled":e.disabled}),[f("span",s({class:e.cx("content")},o.getPTOptions("content")),[h(e.$slots,"default",{},function(){return[h(e.$slots,"icon",{value:e.modelValue,class:I(e.cx("icon"))},function(){return[e.onIcon||e.offIcon?(d(),b("span",s({key:0,class:[e.cx("icon"),e.modelValue?e.onIcon:e.offIcon]},o.getPTOptions("icon")),null,16)):A("",!0)]}),f("span",s({class:e.cx("label")},o.getPTOptions("label")),S(o.label),17)]})],16)],16,N)),[[r]])}O.render=z;var F=function(n){var t=n.dt;return`
.p-selectbutton {
    display: inline-flex;
    user-select: none;
    vertical-align: bottom;
    outline-color: transparent;
    border-radius: `.concat(t("selectbutton.border.radius"),`;
}

.p-selectbutton .p-togglebutton {
    border-radius: 0;
    border-width: 1px 1px 1px 0;
}

.p-selectbutton .p-togglebutton:focus-visible {
    position: relative;
    z-index: 1;
}

.p-selectbutton .p-togglebutton:first-child {
    border-left-width: 1px;
    border-top-left-radius: `).concat(t("selectbutton.border.radius"),`;
    border-bottom-left-radius: `).concat(t("selectbutton.border.radius"),`;
}

.p-selectbutton .p-togglebutton:last-child {
    border-top-right-radius: `).concat(t("selectbutton.border.radius"),`;
    border-bottom-right-radius: `).concat(t("selectbutton.border.radius"),`;
}

.p-selectbutton.p-invalid {
    outline: 1px solid `).concat(t("selectbutton.invalid.border.color"),`;
    outline-offset: 0;
}
`)},H={root:function(n){var t=n.props;return["p-selectbutton p-component",{"p-invalid":t.invalid}]}},G=v.extend({name:"selectbutton",theme:F,classes:H}),M={name:"BaseSelectButton",extends:V,props:{modelValue:null,options:Array,optionLabel:null,optionValue:null,optionDisabled:null,multiple:Boolean,allowEmpty:{type:Boolean,default:!0},invalid:{type:Boolean,default:!1},disabled:Boolean,dataKey:null,ariaLabelledby:{type:String,default:null}},style:G,provide:function(){return{$pcSelectButton:this,$parentInstance:this}}};function U(e,n){var t=typeof Symbol<"u"&&e[Symbol.iterator]||e["@@iterator"];if(!t){if(Array.isArray(e)||(t=B(e))||n){t&&(e=t);var l=0,i=function(){};return{s:i,n:function(){return l>=e.length?{done:!0}:{done:!1,value:e[l++]}},e:function(u){throw u},f:i}}throw new TypeError(`Invalid attempt to iterate non-iterable instance.
In order to be iterable, non-array objects must have a [Symbol.iterator]() method.`)}var o,r=!0,a=!1;return{s:function(){t=t.call(e)},n:function(){var u=t.next();return r=u.done,u},e:function(u){a=!0,o=u},f:function(){try{r||t.return==null||t.return()}finally{if(a)throw o}}}}function W(e){return J(e)||Y(e)||B(e)||X()}function X(){throw new TypeError(`Invalid attempt to spread non-iterable instance.
In order to be iterable, non-array objects must have a [Symbol.iterator]() method.`)}function B(e,n){if(e){if(typeof e=="string")return m(e,n);var t={}.toString.call(e).slice(8,-1);return t==="Object"&&e.constructor&&(t=e.constructor.name),t==="Map"||t==="Set"?Array.from(e):t==="Arguments"||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t)?m(e,n):void 0}}function Y(e){if(typeof Symbol<"u"&&e[Symbol.iterator]!=null||e["@@iterator"]!=null)return Array.from(e)}function J(e){if(Array.isArray(e))return m(e)}function m(e,n){(n==null||n>e.length)&&(n=e.length);for(var t=0,l=Array(n);t<n;t++)l[t]=e[t];return l}var Q={name:"SelectButton",extends:M,inheritAttrs:!1,emits:["update:modelValue","change"],methods:{getOptionLabel:function(n){return this.optionLabel?g(n,this.optionLabel):n},getOptionValue:function(n){return this.optionValue?g(n,this.optionValue):n},getOptionRenderKey:function(n){return this.dataKey?g(n,this.dataKey):this.getOptionLabel(n)},getPTOptions:function(n,t){return this.ptm(t,{context:{active:this.isSelected(n),disabled:this.isOptionDisabled(n),option:n}})},isOptionDisabled:function(n){return this.optionDisabled?g(n,this.optionDisabled):!1},onOptionSelect:function(n,t,l){var i=this;if(!(this.disabled||this.isOptionDisabled(t))){var o=this.isSelected(t);if(!(o&&!this.allowEmpty)){var r=this.getOptionValue(t),a;this.multiple?o?a=this.modelValue.filter(function(c){return!p(c,r,i.equalityKey)}):a=this.modelValue?[].concat(W(this.modelValue),[r]):[r]:a=o?null:r,this.focusedIndex=l,this.$emit("update:modelValue",a),this.$emit("change",{event:n,value:a})}}},isSelected:function(n){var t=!1,l=this.getOptionValue(n);if(this.multiple){if(this.modelValue){var i=U(this.modelValue),o;try{for(i.s();!(o=i.n()).done;){var r=o.value;if(p(r,l,this.equalityKey)){t=!0;break}}}catch(a){i.e(a)}finally{i.f()}}}else t=p(this.modelValue,l,this.equalityKey);return t}},computed:{equalityKey:function(){return this.optionValue?null:this.dataKey}},directives:{ripple:k},components:{ToggleButton:O}},Z=["aria-labelledby"];function _(e,n,t,l,i,o){var r=C("ToggleButton");return d(),b("div",s({class:e.cx("root"),role:"group","aria-labelledby":e.ariaLabelledby},e.ptmi("root")),[(d(!0),b($,null,D(e.options,function(a,c){return d(),K(r,{key:o.getOptionRenderKey(a),modelValue:o.isSelected(a),onLabel:o.getOptionLabel(a),offLabel:o.getOptionLabel(a),disabled:e.disabled||o.isOptionDisabled(a),unstyled:e.unstyled,onChange:function(L){return o.onOptionSelect(L,a,c)},pt:e.ptm("pcButton")},x({_:2},[e.$slots.option?{name:"default",fn:P(function(){return[h(e.$slots,"option",{option:a,index:c},function(){return[f("span",s({ref_for:!0},e.ptm("pcButton").label),S(o.getOptionLabel(a)),17)]})]}),key:"0"}:void 0]),1032,["modelValue","onLabel","offLabel","disabled","unstyled","onChange","pt"])}),128))],16,Z)}Q.render=_;export{O as a,Q as s};
