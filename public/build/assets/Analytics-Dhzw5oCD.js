import{T as w}from"./index-BeMiMaX7.js";import{B as U,o as i,l,D as C,j as n,a as G,b as M,r as k,d as H,e as A,f as E,i as c,g as u,Z as J,q as R,p as x,s as Q,t as v,U as S,m as Z,n as K,V as X,W as Y,a1 as I,a0 as ee,x as te,y as se,k as ne}from"./app-B4oSAkVP.js";import{_ as ae}from"./AppLayout-CEyAmMP5.js";import{E as oe}from"./jspdf.es.min-BkPDM2NB.js";import re from"./html2canvas.esm-CBrSDip1.js";import{s as ie}from"./index-CHQH7jRd.js";import{s as b}from"./index-DVIMWJjq.js";import{a as le}from"./index-DzCpZxvp.js";import{s as ce}from"./index-DdT1qFJk.js";import{C as pe,p as de,a as ue,b as me,B as fe,c as he,L as ge,P as ve,d as ye,A as xe,R as be}from"./chart-B59wzfTt.js";import{B as z,L as _e,P as ke,D as Se,R as Ce}from"./index-Dd2PTv0T.js";import{_ as Ee}from"./_plugin-vue_export-helper-DlAUqK2U.js";import"./DropdownLink-B59JWmkM.js";import"./index-LzhPMz77.js";import"./index-DjU5rnlX.js";import"./index-Dhf6pfmr.js";import"./index-BM7dsjYe.js";import"./index-gwiffqIQ.js";import"./index-W2upbyuC.js";import"./index-JNFFLNI-.js";import"./index-DZohkDvD.js";import"./index-C20p2cSe.js";import"./index-DzuzenO3.js";var $e=function(p){var m=p.dt;return`
.p-progressspinner {
    position: relative;
    margin: 0 auto;
    width: 100px;
    height: 100px;
    display: inline-block;
}

.p-progressspinner::before {
    content: "";
    display: block;
    padding-top: 100%;
}

.p-progressspinner-spin {
    height: 100%;
    transform-origin: center center;
    width: 100%;
    position: absolute;
    top: 0;
    bottom: 0;
    left: 0;
    right: 0;
    margin: auto;
    animation: p-progressspinner-rotate 2s linear infinite;
}

.p-progressspinner-circle {
    stroke-dasharray: 89, 200;
    stroke-dashoffset: 0;
    stroke: `.concat(m("progressspinner.color.1"),`;
    animation: p-progressspinner-dash 1.5s ease-in-out infinite, p-progressspinner-color 6s ease-in-out infinite;
    stroke-linecap: round;
}

@keyframes p-progressspinner-rotate {
    100% {
        transform: rotate(360deg);
    }
}
@keyframes p-progressspinner-dash {
    0% {
        stroke-dasharray: 1, 200;
        stroke-dashoffset: 0;
    }
    50% {
        stroke-dasharray: 89, 200;
        stroke-dashoffset: -35px;
    }
    100% {
        stroke-dasharray: 89, 200;
        stroke-dashoffset: -124px;
    }
}
@keyframes p-progressspinner-color {
    100%,
    0% {
        stroke: `).concat(m("progressspinner.color.1"),`;
    }
    40% {
        stroke: `).concat(m("progressspinner.color.2"),`;
    }
    66% {
        stroke: `).concat(m("progressspinner.color.3"),`;
    }
    80%,
    90% {
        stroke: `).concat(m("progressspinner.color.4"),`;
    }
}
`)},Pe={root:"p-progressspinner",spin:"p-progressspinner-spin",circle:"p-progressspinner-circle"},Te=U.extend({name:"progressspinner",theme:$e,classes:Pe}),Be={name:"BaseProgressSpinner",extends:le,props:{strokeWidth:{type:String,default:"2"},fill:{type:String,default:"none"},animationDuration:{type:String,default:"2s"}},style:Te,provide:function(){return{$pcProgressSpinner:this,$parentInstance:this}}},L={name:"ProgressSpinner",extends:Be,inheritAttrs:!1,computed:{svgStyle:function(){return{"animation-duration":this.animationDuration}}}},De=["fill","stroke-width"];function Ae(o,p,m,y,f,r){return i(),l("div",C({class:o.cx("root"),role:"progressbar"},o.ptmi("root")),[(i(),l("svg",C({class:o.cx("spin"),viewBox:"25 25 50 50",style:r.svgStyle},o.ptm("spin")),[n("circle",C({class:o.cx("circle"),cx:"50",cy:"50",r:"20",fill:o.fill,"stroke-width":o.strokeWidth,strokeMiterlimit:"10"},o.ptm("circle")),null,16,De)],16))],16)}L.render=Ae;const _=o=>(te("data-v-63549000"),o=o(),se(),o),Re={class:"bg-[#f8fafc] min-h-screen p-6"},Ie={class:"flex justify-between items-end mb-8"},ze=_(()=>n("div",null,[n("h1",{class:"text-2xl font-black tracking-tighter text-slate-900 uppercase"},[ne(" Repporting "),n("span",{class:"text-primary-600"},"Data")]),n("p",{class:"text-slate-500 text-xs font-medium"},"Reporting Haute Fidélité v11.5")],-1)),Le={key:0,class:"flex gap-2"},Fe={class:"flex bg-white rounded-xl shadow-sm border border-slate-200 p-1"},We={class:"mb-10"},Ne={class:"p-2"},Ve=["onClick"],qe=_(()=>n("div",{class:"icon-box"},[n("i",{class:"pi pi-file text-xl"})],-1)),Oe={class:"content"},je={class:"title"},we=_(()=>n("span",{class:"subtitle"},"Format A4",-1)),Ue={class:"viewport-container"},Ge={class:"flex items-center gap-6 mb-8 bg-white/80 backdrop-blur-md border border-white px-4 py-2 rounded-2xl shadow-sm"},Me={class:"text-[11px] font-black text-slate-400 uppercase tracking-widest"},He={key:1,class:"w-full h-full p-2"},Je={key:2,class:"w-full h-full flex flex-col justify-center text-center"},Qe={class:"text-[9px] font-black text-slate-400 uppercase tracking-widest"},Ze={key:3,class:"absolute inset-0 bg-white/60 backdrop-blur-[2px] flex items-center justify-center"},Ke={key:1,class:"empty-state"},Xe=_(()=>n("i",{class:"pi pi-clone text-4xl text-slate-300 mb-4"},null,-1)),Ye=_(()=>n("h3",{class:"text-slate-400 font-bold uppercase tracking-widest text-sm"},"Sélectionnez un modèle",-1)),et=[Xe,Ye],tt={__name:"Analytics",props:{reportTemplates:{type:Array,default:()=>[]}},setup(o){pe.register(de,ue,me,fe,he,ge,ve,ye,xe,be);const p=G(),m=M(),y=k(!1),f=k(null),r=k([]),d=k(0),$=e=>{var a;return((a=f.value)==null?void 0:a.id)===e},F=async e=>{if(!$(e.id)){y.value=!0,f.value=e;try{await new Promise(a=>setTimeout(a,400)),r.value=typeof e.content=="string"?JSON.parse(e.content):e.content,d.value=0,await P()}catch{p.add({severity:"error",summary:"Erreur",detail:"Contenu du template corrompu."}),r.value=[]}finally{y.value=!1}}},P=async()=>{if(!r.value.length)return;const e=[];r.value.forEach(a=>{a.widgets.forEach(s=>{if(["chart","kpi"].includes(s.type)){s.isSyncing=!0;const t={type:s.type,config:s.type==="chart"?{sources:s.dataSources,timeScale:s.config.timeScale||"days"}:{model:s.dataSource,column:s.dataColumn,method:s.dataMethod||"COUNT"}},h=I.post(route("quantum.query"),t).then(g=>{s.type==="chart"&&(s.data=g.data),s.type==="kpi"&&(s.config.value=g.data.value)}).finally(()=>s.isSyncing=!1);e.push(h)}})}),await Promise.all(e)},W=async()=>{y.value=!0,p.add({severity:"info",summary:"Export",detail:"Génération du document multi-pages..."});try{const e=new oe("p","mm","a4"),a=document.getElementById("report-canvas");for(let s=0;s<r.value.length;s++){d.value=s,await ee(),await new Promise(g=>setTimeout(g,600));const h=(await re(a,{scale:2,useCORS:!0})).toDataURL("image/png");s>0&&e.addPage(),e.addImage(h,"PNG",0,0,210,297)}e.save(`Rapport_${f.value.name}.pdf`),p.add({severity:"success",summary:"Succès",detail:"PDF téléchargé."})}catch{p.add({severity:"error",summary:"Erreur Export",detail:"Échec de la génération."})}finally{y.value=!1}},N=()=>{m.require({message:"Souhaitez-vous recevoir ce rapport par email immédiatement ?",header:"Envoi par Email",icon:"pi pi-envelope",accept:async()=>{try{await I.post(route("quantum.share"),{template_id:f.value.id,pages:r.value}),p.add({severity:"success",summary:"Envoyé",detail:"Vérifiez votre boîte de réception."})}catch{p.add({severity:"error",summary:"Erreur",detail:"Impossible d'envoyer l'email."})}}})},V=H(()=>{const e=r.value[d.value];if(!e)return{};const a=e.format||{w:793,h:1122},s=e.orientation==="landscape";return{width:(s?a.h:a.w)+"px",height:(s?a.w:a.h)+"px",backgroundColor:e.background||"#ffffff",transform:"scale(0.75)",transformOrigin:"top center"}}),q=e=>{var a,s,t,h,g,T,B,D;return{position:"absolute",left:e.x+"px",top:e.y+"px",width:e.w+"px",height:e.h+"px",zIndex:((a=e.style)==null?void 0:a.zIndex)||10,backgroundColor:((s=e.style)==null?void 0:s.backgroundColor)||"transparent",borderRadius:(((t=e.style)==null?void 0:t.borderRadius)||0)+"px",border:`${((h=e.style)==null?void 0:h.borderWidth)||0}px ${((g=e.style)==null?void 0:g.borderStyle)||"solid"} ${((T=e.style)==null?void 0:T.borderColor)||"#000"}`,transform:`rotate(${((B=e.style)==null?void 0:B.rotation)||0}deg)`,padding:(((D=e.style)==null?void 0:D.padding)||0)+"px",overflow:"hidden"}},O=e=>({bar:z,line:_e,pie:ke,doughnut:Se,radar:Ce})[e]||z,j={responsive:!0,maintainAspectRatio:!1,plugins:{legend:{position:"bottom",labels:{boxWidth:10,font:{size:10}}}}};return(e,a)=>{const s=w;return i(),A(ae,null,{default:E(()=>[c(u(J),{title:"Quantum Analytics"}),c(u(ce)),n("div",Re,[n("header",Ie,[ze,f.value?(i(),l("div",Le,[c(u(b),{icon:"pi pi-refresh",onClick:P,loading:y.value,class:"p-button-sm p-button-text p-button-secondary"},null,8,["loading"]),n("div",Fe,[R(c(u(b),{icon:"pi pi-file-pdf",onClick:W,class:"p-button-text p-button-sm p-button-secondary"},null,512),[[s,"Télécharger PDF",void 0,{bottom:!0}]]),R(c(u(b),{icon:"pi pi-envelope",onClick:N,class:"p-button-text p-button-sm p-button-secondary"},null,512),[[s,"Envoyer Email",void 0,{bottom:!0}]])])])):x("",!0)]),n("section",We,[c(u(ie),{value:o.reportTemplates,numVisible:4,numScroll:1,circular:!0,class:"custom-carousel"},{item:E(t=>[n("div",Ne,[n("div",{onClick:h=>F(t.data),class:Q(["card-v11",$(t.data.id)?"active":""])},[qe,n("div",Oe,[n("span",je,v(t.data.name),1),we])],10,Ve)])]),_:1},8,["value"])]),n("div",Ue,[c(Y,{name:"quantum-fade",mode:"out-in"},{default:E(()=>[f.value&&r.value.length>0?(i(),l("div",{key:f.value.id,class:"flex flex-col items-center"},[n("div",Ge,[c(u(b),{icon:"pi pi-chevron-left",onClick:a[0]||(a[0]=t=>d.value--),disabled:d.value===0,class:"p-button-rounded p-button-text p-button-sm"},null,8,["disabled"]),n("span",Me,"Page "+v(d.value+1)+" / "+v(r.value.length),1),c(u(b),{icon:"pi pi-chevron-right",onClick:a[1]||(a[1]=t=>d.value++),disabled:d.value===r.value.length-1,class:"p-button-rounded p-button-text p-button-sm"},null,8,["disabled"])]),n("div",{id:"report-canvas",style:S(V.value),class:"relative bg-white shadow-2xl"},[(i(!0),l(Z,null,K(r.value[d.value].widgets,t=>(i(),l("div",{key:t.id,style:S(q(t))},[t.type==="text"?(i(),l("div",{key:0,style:S({fontFamily:t.config.font,fontSize:t.config.size+"px",color:t.config.color,textAlign:t.config.align,fontWeight:t.config.weight})},v(t.content),5)):x("",!0),t.type==="chart"?(i(),l("div",He,[(i(),A(X(O(t.chartType)),{data:t.data,options:j},null,8,["data"]))])):x("",!0),t.type==="kpi"?(i(),l("div",Je,[n("span",Qe,v(t.config.label),1),n("div",{class:"text-3xl font-black",style:S({color:t.config.color||"#4F46E5"})},v(t.config.prefix||"")+v(t.config.value),5)])):x("",!0),t.isSyncing?(i(),l("div",Ze,[c(u(L),{style:{width:"20px",height:"20px"}})])):x("",!0)],4))),128))],4)])):(i(),l("div",Ke,et))]),_:1})])])]),_:1})}}},Et=Ee(tt,[["__scopeId","data-v-63549000"]]);export{Et as default};
