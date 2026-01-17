import{B as c,ak as f,al as m,a5 as p,o,l as d,j as b,D as l,p as u}from"./app-gDNqx2_D.js";import{a as v}from"./index-DnMOyY1I.js";var y=function(e){var t=e.dt;return`
.p-slider {
    position: relative;
    background: `.concat(t("slider.track.background"),`;
    border-radius: `).concat(t("slider.border.radius"),`;
}

.p-slider-handle {
    cursor: grab;
    touch-action: none;
    display: flex;
    justify-content: center;
    align-items: center;
    height: `).concat(t("slider.handle.height"),`;
    width: `).concat(t("slider.handle.width"),`;
    background: `).concat(t("slider.handle.background"),`;
    border-radius: `).concat(t("slider.handle.border.radius"),`;
    transition: background `).concat(t("slider.transition.duration"),", color ").concat(t("slider.transition.duration"),", border-color ").concat(t("slider.transition.duration"),", box-shadow ").concat(t("slider.transition.duration"),", outline-color ").concat(t("slider.transition.duration"),`;
    outline-color: transparent;
}

.p-slider-handle::before {
    content: "";
    width: `).concat(t("slider.handle.content.width"),`;
    height: `).concat(t("slider.handle.content.height"),`;
    display: block;
    background: `).concat(t("slider.handle.content.background"),`;
    border-radius: `).concat(t("slider.handle.content.border.radius"),`;
    box-shadow: `).concat(t("slider.handle.content.shadow"),`;
    transition: background `).concat(t("slider.transition.duration"),`;
}

.p-slider:not(.p-disabled) .p-slider-handle:hover {
    background: `).concat(t("slider.handle.hover.background"),`;
}

.p-slider:not(.p-disabled) .p-slider-handle:hover::before {
    background: `).concat(t("slider.handle.content.hover.background"),`;
}

.p-slider-handle:focus-visible {
    border-color: `).concat(t("slider.handle.focus.border.color"),`;
    box-shadow: `).concat(t("slider.handle.focus.ring.shadow"),`;
    outline: `).concat(t("slider.handle.focus.ring.width")," ").concat(t("slider.handle.focus.ring.style")," ").concat(t("slider.handle.focus.ring.color"),`;
    outline-offset: `).concat(t("slider.handle.focus.ring.offset"),`;
}

.p-slider-range {
    display: block;
    background: `).concat(t("slider.range.background"),`;
    border-radius: `).concat(t("slider.border.radius"),`;
}

.p-slider.p-slider-horizontal {
    height: `).concat(t("slider.track.size"),`;
}

.p-slider-horizontal .p-slider-range {
    top: 0;
    left: 0;
    height: 100%;
}

.p-slider-horizontal .p-slider-handle {
    top: 50%;
    margin-top: calc(-1 * calc(`).concat(t("slider.handle.height"),` / 2));
    margin-left: calc(-1 * calc(`).concat(t("slider.handle.width"),` / 2));
}

.p-slider-vertical {
    min-height: 100px;
    width: `).concat(t("slider.track.size"),`;
}

.p-slider-vertical .p-slider-handle {
    left: 50%;
    margin-left: calc(-1 * calc(`).concat(t("slider.handle.width"),` / 2));
    margin-bottom: calc(-1 * calc(`).concat(t("slider.handle.height"),` / 2));
}

.p-slider-vertical .p-slider-range {
    bottom: 0;
    left: 0;
    width: 100%;
}
`)},D={handle:{position:"absolute"},range:{position:"absolute"}},w={root:function(e){var t=e.props;return["p-slider p-component",{"p-disabled":t.disabled,"p-slider-horizontal":t.orientation==="horizontal","p-slider-vertical":t.orientation==="vertical"}]},range:"p-slider-range",handle:"p-slider-handle"},S=c.extend({name:"slider",theme:y,classes:w,inlineStyles:D}),k={name:"BaseSlider",extends:v,props:{modelValue:[Number,Array],min:{type:Number,default:0},max:{type:Number,default:100},orientation:{type:String,default:"horizontal"},step:{type:Number,default:null},range:{type:Boolean,default:!1},disabled:{type:Boolean,default:!1},tabindex:{type:Number,default:0},ariaLabelledby:{type:String,default:null},ariaLabel:{type:String,default:null}},style:S,provide:function(){return{$pcSlider:this,$parentInstance:this}}};function L(n){return V(n)||A(n)||P(n)||E()}function E(){throw new TypeError(`Invalid attempt to spread non-iterable instance.
In order to be iterable, non-array objects must have a [Symbol.iterator]() method.`)}function P(n,e){if(n){if(typeof n=="string")return h(n,e);var t={}.toString.call(n).slice(8,-1);return t==="Object"&&n.constructor&&(t=n.constructor.name),t==="Map"||t==="Set"?Array.from(n):t==="Arguments"||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t)?h(n,e):void 0}}function A(n){if(typeof Symbol<"u"&&n[Symbol.iterator]!=null||n["@@iterator"]!=null)return Array.from(n)}function V(n){if(Array.isArray(n))return h(n)}function h(n,e){(e==null||e>n.length)&&(e=n.length);for(var t=0,r=Array(e);t<e;t++)r[t]=n[t];return r}var M={name:"Slider",extends:k,inheritAttrs:!1,emits:["update:modelValue","change","slideend"],dragging:!1,handleIndex:null,initX:null,initY:null,barWidth:null,barHeight:null,dragListener:null,dragEndListener:null,beforeUnmount:function(){this.unbindDragListeners()},methods:{updateDomData:function(){var e=this.$el.getBoundingClientRect();this.initX=e.left+f(),this.initY=e.top+m(),this.barWidth=this.$el.offsetWidth,this.barHeight=this.$el.offsetHeight},setValue:function(e){var t,r=e.touches?e.touches[0].pageX:e.pageX,s=e.touches?e.touches[0].pageY:e.pageY;this.orientation==="horizontal"?t=(r-this.initX)*100/this.barWidth:t=(this.initY+this.barHeight-s)*100/this.barHeight;var a=(this.max-this.min)*(t/100)+this.min;if(this.step){var i=this.range?this.value[this.handleIndex]:this.value,g=a-i;g<0?a=i+Math.ceil(a/this.step-i/this.step)*this.step:g>0&&(a=i+Math.floor(a/this.step-i/this.step)*this.step)}else a=Math.floor(a);this.updateModel(e,a)},updateModel:function(e,t){var r=parseFloat(t.toFixed(10)),s;this.range?(s=this.value?L(this.value):[],this.handleIndex==0?(r<this.min?r=this.min:r>=this.max&&(r=this.max),s[0]=r):(r>this.max?r=this.max:r<=this.min&&(r=this.min),s[1]=r)):(r<this.min?r=this.min:r>this.max&&(r=this.max),s=r),this.$emit("update:modelValue",s),this.$emit("change",s)},onDragStart:function(e,t){this.disabled||(this.$el.setAttribute("data-p-sliding",!0),this.dragging=!0,this.updateDomData(),this.range&&this.value[0]===this.max?this.handleIndex=0:this.handleIndex=t,e.currentTarget.focus(),e.preventDefault())},onDrag:function(e){this.dragging&&(this.setValue(e),e.preventDefault())},onDragEnd:function(e){this.dragging&&(this.dragging=!1,this.$el.setAttribute("data-p-sliding",!1),this.$emit("slideend",{originalEvent:e,value:this.value}))},onBarClick:function(e){this.disabled||p(e.target,"data-pc-section")!=="handle"&&(this.updateDomData(),this.setValue(e))},onMouseDown:function(e,t){this.bindDragListeners(),this.onDragStart(e,t)},onKeyDown:function(e,t){switch(this.handleIndex=t,e.code){case"ArrowDown":case"ArrowLeft":this.decrementValue(e,t),e.preventDefault();break;case"ArrowUp":case"ArrowRight":this.incrementValue(e,t),e.preventDefault();break;case"PageDown":this.decrementValue(e,t,!0),e.preventDefault();break;case"PageUp":this.incrementValue(e,t,!0),e.preventDefault();break;case"Home":this.updateModel(e,this.min),e.preventDefault();break;case"End":this.updateModel(e,this.max),e.preventDefault();break}},decrementValue:function(e,t){var r=arguments.length>2&&arguments[2]!==void 0?arguments[2]:!1,s;this.range?this.step?s=this.value[t]-this.step:s=this.value[t]-1:this.step?s=this.value-this.step:!this.step&&r?s=this.value-10:s=this.value-1,this.updateModel(e,s),e.preventDefault()},incrementValue:function(e,t){var r=arguments.length>2&&arguments[2]!==void 0?arguments[2]:!1,s;this.range?this.step?s=this.value[t]+this.step:s=this.value[t]+1:this.step?s=this.value+this.step:!this.step&&r?s=this.value+10:s=this.value+1,this.updateModel(e,s),e.preventDefault()},bindDragListeners:function(){this.dragListener||(this.dragListener=this.onDrag.bind(this),document.addEventListener("mousemove",this.dragListener)),this.dragEndListener||(this.dragEndListener=this.onDragEnd.bind(this),document.addEventListener("mouseup",this.dragEndListener))},unbindDragListeners:function(){this.dragListener&&(document.removeEventListener("mousemove",this.dragListener),this.dragListener=null),this.dragEndListener&&(document.removeEventListener("mouseup",this.dragEndListener),this.dragEndListener=null)}},computed:{value:function(){var e;if(this.range){var t,r,s,a;return[(t=(r=this.modelValue)===null||r===void 0?void 0:r[0])!==null&&t!==void 0?t:this.min,(s=(a=this.modelValue)===null||a===void 0?void 0:a[1])!==null&&s!==void 0?s:this.max]}return(e=this.modelValue)!==null&&e!==void 0?e:this.min},horizontal:function(){return this.orientation==="horizontal"},vertical:function(){return this.orientation==="vertical"},rangeStyle:function(){if(this.range){var e=this.rangeEndPosition>this.rangeStartPosition?this.rangeEndPosition-this.rangeStartPosition:this.rangeStartPosition-this.rangeEndPosition,t=this.rangeEndPosition>this.rangeStartPosition?this.rangeStartPosition:this.rangeEndPosition;return this.horizontal?{left:t+"%",width:e+"%"}:{bottom:t+"%",height:e+"%"}}else return this.horizontal?{width:this.handlePosition+"%"}:{height:this.handlePosition+"%"}},handleStyle:function(){return this.horizontal?{left:this.handlePosition+"%"}:{bottom:this.handlePosition+"%"}},handlePosition:function(){return this.value<this.min?0:this.value>this.max?100:(this.value-this.min)*100/(this.max-this.min)},rangeStartPosition:function(){return this.value&&this.value[0]?(this.value[0]<this.min?0:this.value[0]-this.min)*100/(this.max-this.min):0},rangeEndPosition:function(){return this.value&&this.value.length===2?(this.value[1]>this.max?100:this.value[1]-this.min)*100/(this.max-this.min):100},rangeStartHandleStyle:function(){return this.horizontal?{left:this.rangeStartPosition+"%"}:{bottom:this.rangeStartPosition+"%"}},rangeEndHandleStyle:function(){return this.horizontal?{left:this.rangeEndPosition+"%"}:{bottom:this.rangeEndPosition+"%"}}}},z=["tabindex","aria-valuemin","aria-valuenow","aria-valuemax","aria-labelledby","aria-label","aria-orientation"],x=["tabindex","aria-valuemin","aria-valuenow","aria-valuemax","aria-labelledby","aria-label","aria-orientation"],H=["tabindex","aria-valuemin","aria-valuenow","aria-valuemax","aria-labelledby","aria-label","aria-orientation"];function T(n,e,t,r,s,a){return o(),d("div",l({class:n.cx("root"),onClick:e[15]||(e[15]=function(){return a.onBarClick&&a.onBarClick.apply(a,arguments)})},n.ptmi("root"),{"data-p-sliding":!1}),[b("span",l({class:n.cx("range"),style:[n.sx("range"),a.rangeStyle]},n.ptm("range")),null,16),n.range?u("",!0):(o(),d("span",l({key:0,class:n.cx("handle"),style:[n.sx("handle"),a.handleStyle],onTouchstartPassive:e[0]||(e[0]=function(i){return a.onDragStart(i)}),onTouchmovePassive:e[1]||(e[1]=function(i){return a.onDrag(i)}),onTouchend:e[2]||(e[2]=function(i){return a.onDragEnd(i)}),onMousedown:e[3]||(e[3]=function(i){return a.onMouseDown(i)}),onKeydown:e[4]||(e[4]=function(i){return a.onKeyDown(i)}),tabindex:n.tabindex,role:"slider","aria-valuemin":n.min,"aria-valuenow":n.modelValue,"aria-valuemax":n.max,"aria-labelledby":n.ariaLabelledby,"aria-label":n.ariaLabel,"aria-orientation":n.orientation},n.ptm("handle")),null,16,z)),n.range?(o(),d("span",l({key:1,class:n.cx("handle"),style:[n.sx("handle"),a.rangeStartHandleStyle],onTouchstartPassive:e[5]||(e[5]=function(i){return a.onDragStart(i,0)}),onTouchmovePassive:e[6]||(e[6]=function(i){return a.onDrag(i)}),onTouchend:e[7]||(e[7]=function(i){return a.onDragEnd(i)}),onMousedown:e[8]||(e[8]=function(i){return a.onMouseDown(i,0)}),onKeydown:e[9]||(e[9]=function(i){return a.onKeyDown(i,0)}),tabindex:n.tabindex,role:"slider","aria-valuemin":n.min,"aria-valuenow":n.modelValue?n.modelValue[0]:null,"aria-valuemax":n.max,"aria-labelledby":n.ariaLabelledby,"aria-label":n.ariaLabel,"aria-orientation":n.orientation},n.ptm("startHandler")),null,16,x)):u("",!0),n.range?(o(),d("span",l({key:2,class:n.cx("handle"),style:[n.sx("handle"),a.rangeEndHandleStyle],onTouchstartPassive:e[10]||(e[10]=function(i){return a.onDragStart(i,1)}),onTouchmovePassive:e[11]||(e[11]=function(i){return a.onDrag(i)}),onTouchend:e[12]||(e[12]=function(i){return a.onDragEnd(i)}),onMousedown:e[13]||(e[13]=function(i){return a.onMouseDown(i,1)}),onKeydown:e[14]||(e[14]=function(i){return a.onKeyDown(i,1)}),tabindex:n.tabindex,role:"slider","aria-valuemin":n.min,"aria-valuenow":n.modelValue?n.modelValue[1]:null,"aria-valuemax":n.max,"aria-labelledby":n.ariaLabelledby,"aria-label":n.ariaLabel,"aria-orientation":n.orientation},n.ptm("endHandler")),null,16,H)):u("",!0)],16)}M.render=T;export{M as s};
