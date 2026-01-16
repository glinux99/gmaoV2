import{a as i}from"./index-DGDWVGXJ.js";import{B as s,o as t,l as e,E as p,D as o,t as c,e as v,s as d,V as u,p as g}from"./app-BTm5MPCd.js";var m=function(r){var n=r.dt;return`
.p-avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: `.concat(n("avatar.width"),`;
    height: `).concat(n("avatar.height"),`;
    font-size: `).concat(n("avatar.font.size"),`;
    background: `).concat(n("avatar.background"),`;
    border-radius: `).concat(n("avatar.border.radius"),`;
}

.p-avatar-image {
    background: transparent;
}

.p-avatar-circle {
    border-radius: 50%;
}

.p-avatar-circle img {
    border-radius: 50%;
}

.p-avatar-icon {
    font-size: `).concat(n("avatar.font.size"),`;
}

.p-avatar img {
    width: 100%;
    height: 100%;
}

.p-avatar-lg {
    width: `).concat(n("avatar.lg.width"),`;
    height: `).concat(n("avatar.lg.width"),`;
    font-size: `).concat(n("avatar.lg.font.size"),`;
}

.p-avatar-lg .p-avatar-icon {
    font-size: `).concat(n("avatar.lg.font.size"),`;
}

.p-avatar-xl {
    width: `).concat(n("avatar.xl.width"),`;
    height: `).concat(n("avatar.xl.width"),`;
    font-size: `).concat(n("avatar.xl.font.size"),`;
}

.p-avatar-xl .p-avatar-icon {
    font-size: `).concat(n("avatar.xl.font.size"),`;
}

.p-avatar-group {
    display: flex;
    align-items: center;
}

.p-avatar-group .p-avatar + .p-avatar {
    margin-left: `).concat(n("avatar.group.offset"),`;
}

.p-avatar-group .p-avatar {
    border: 2px solid `).concat(n("avatar.group.border.color"),`;
}
`)},f={root:function(r){var n=r.props;return["p-avatar p-component",{"p-avatar-image":n.image!=null,"p-avatar-circle":n.shape==="circle","p-avatar-lg":n.size==="large","p-avatar-xl":n.size==="xlarge"}]},label:"p-avatar-label",icon:"p-avatar-icon"},h=s.extend({name:"avatar",theme:m,classes:f}),b={name:"BaseAvatar",extends:i,props:{label:{type:String,default:null},icon:{type:String,default:null},image:{type:String,default:null},size:{type:String,default:"normal"},shape:{type:String,default:"square"},ariaLabelledby:{type:String,default:null},ariaLabel:{type:String,default:null}},style:h,provide:function(){return{$pcAvatar:this,$parentInstance:this}}},y={name:"Avatar",extends:b,inheritAttrs:!1,emits:["error"],methods:{onError:function(r){this.$emit("error",r)}}},z=["aria-labelledby","aria-label"],S=["src","alt"];function k(a,r,n,w,$,l){return t(),e("div",o({class:a.cx("root"),"aria-labelledby":a.ariaLabelledby,"aria-label":a.ariaLabel},a.ptmi("root")),[p(a.$slots,"default",{},function(){return[a.label?(t(),e("span",o({key:0,class:a.cx("label")},a.ptm("label")),c(a.label),17)):a.$slots.icon?(t(),v(u(a.$slots.icon),{key:1,class:d(a.cx("icon"))},null,8,["class"])):a.icon?(t(),e("span",o({key:2,class:[a.cx("icon"),a.icon]},a.ptm("icon")),null,16)):a.image?(t(),e("img",o({key:3,src:a.image,alt:a.ariaLabel,onError:r[0]||(r[0]=function(){return l.onError&&l.onError.apply(l,arguments)})},a.ptm("image")),null,16,S)):g("",!0)]})],16,z)}y.render=k;export{y as s};
