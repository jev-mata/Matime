import{D as C,v as L,ay as y,q as ee,a9 as g,aa as j,ab as pe,ac as me,c as _,az as te,a7 as ne,a8 as we,O as G,I as $,J as q,u as oe,a2 as ye,r as H,Y as J,ae as re,B as he,V as K,aA as ge}from"./app-Dzd5B06R.js";function et(e,t){var o;const n=g();return K(()=>{n.value=e()},{...t,flush:(o=void 0)!=null?o:"sync"}),j(n)}function W(e){return pe()?(me(e),!0):!1}function tt(){const e=new Set,t=r=>{e.delete(r)};return{on:r=>{e.add(r);const l=()=>t(r);return W(l),{off:l}},off:t,trigger:(...r)=>Promise.all(Array.from(e).map(l=>l(...r))),clear:()=>{e.clear()}}}function nt(e){let t=!1,o;const n=ne(!0);return(...a)=>(t||(o=n.run(()=>e(...a)),t=!0),o)}const B=new WeakMap,be=(...e)=>{var t;const o=e[0],n=(t=J())==null?void 0:t.proxy;if(n==null&&!re())throw new Error("injectLocal must be called in setup");return n&&B.has(n)&&o in B.get(n)?B.get(n)[o]:he(...e)};function ot(e){let t=0,o,n;const a=()=>{t-=1,n&&t<=0&&(n.stop(),o=void 0,n=void 0)};return(...r)=>(t+=1,n||(n=ne(!0),o=n.run(()=>e(...r))),W(a),o)}function Se(e){if(!$(e))return q(e);const t=new Proxy({},{get(o,n,a){return oe(Reflect.get(e.value,n,a))},set(o,n,a){return $(e.value[n])&&!$(a)?e.value[n].value=a:e.value[n]=a,!0},deleteProperty(o,n){return Reflect.deleteProperty(e.value,n)},has(o,n){return Reflect.has(e.value,n)},ownKeys(){return Object.keys(e.value)},getOwnPropertyDescriptor(){return{enumerable:!0,configurable:!0}}});return q(t)}function Oe(e){return Se(_(e))}function rt(e,...t){const o=t.flat(),n=o[0];return Oe(()=>Object.fromEntries(typeof n=="function"?Object.entries(G(e)).filter(([a,r])=>!n(y(r),a)):Object.entries(G(e)).filter(a=>!o.includes(a[0]))))}const U=typeof window<"u"&&typeof document<"u";typeof WorkerGlobalScope<"u"&&globalThis instanceof WorkerGlobalScope;const _e=e=>typeof e<"u",ie=e=>e!=null,Ee=Object.prototype.toString,Ae=e=>Ee.call(e)==="[object Object]",I=()=>{},it=Ce();function Ce(){var e,t;return U&&((e=window==null?void 0:window.navigator)==null?void 0:e.userAgent)&&(/iP(?:ad|hone|od)/.test(window.navigator.userAgent)||((t=window==null?void 0:window.navigator)==null?void 0:t.maxTouchPoints)>2&&/iPad|Macintosh/.test(window==null?void 0:window.navigator.userAgent))}function Ne(e,t){function o(...n){return new Promise((a,r)=>{Promise.resolve(e(()=>t.apply(this,n),{fn:t,thisArg:this,args:n})).then(a).catch(r)})}return o}const se=e=>e();function Te(e=se,t={}){const{initialState:o="active"}=t,n=Pe(o==="active");function a(){n.value=!1}function r(){n.value=!0}const l=(...s)=>{n.value&&e(...s)};return{isActive:j(n),pause:a,resume:r,eventFilter:l}}function Me(e){let t;function o(){return t||(t=e()),t}return o.reset=async()=>{const n=t;t=void 0,n&&await n},o}function Q(e){return e.endsWith("rem")?Number.parseFloat(e)*16:Number.parseFloat(e)}function ae(e){return J()}function z(e){return Array.isArray(e)?e:[e]}function Pe(...e){if(e.length!==1)return ye(...e);const t=e[0];return typeof t=="function"?j(te(()=>({get:t,set:I}))):H(t)}function st(e,t=1e4){return te((o,n)=>{let a=y(e),r;const l=()=>setTimeout(()=>{a=y(e),n()},y(t));return W(()=>{clearTimeout(r)}),{get(){return o(),a},set(s){a=s,n(),clearTimeout(r),r=l()}}})}function We(e,t,o={}){const{eventFilter:n=se,...a}=o;return C(e,Ne(n,t),a)}function Ve(e,t,o={}){const{eventFilter:n,initialState:a="active",...r}=o,{eventFilter:l,pause:s,resume:c,isActive:u}=Te(n,{initialState:a});return{stop:We(e,t,{...r,eventFilter:l}),pause:s,resume:c,isActive:u}}const at=y;function ut(e,t){ae()&&we(e,t)}function Fe(e,t=!0,o){ae()?ee(e,o):t?e():L(e)}function Re(e,t,o={}){const{immediate:n=!0,immediateCallback:a=!1}=o,r=g(!1);let l=null;function s(){l&&(clearTimeout(l),l=null)}function c(){r.value=!1,s()}function u(...i){a&&e(),s(),r.value=!0,l=setTimeout(()=>{r.value=!1,l=null,e(...i)},y(t))}return n&&(r.value=!0,U&&u()),W(c),{isPending:j(r),start:u,stop:c}}function xe(e,t,o){return C(e,t,{...o,immediate:!0})}function Ie(e,t,o){const n=C(e,(...a)=>(L(()=>n()),t(...a)),o);return n}const A=U?window:void 0,ue=U?window.navigator:void 0;function M(e){var t;const o=y(e);return(t=o==null?void 0:o.$el)!=null?t:o}function N(...e){const t=[],o=()=>{t.forEach(s=>s()),t.length=0},n=(s,c,u,i)=>(s.addEventListener(c,u,i),()=>s.removeEventListener(c,u,i)),a=_(()=>{const s=z(y(e[0])).filter(c=>c!=null);return s.every(c=>typeof c!="string")?s:void 0}),r=xe(()=>{var s,c;return[(c=(s=a.value)==null?void 0:s.map(u=>M(u)))!=null?c:[A].filter(u=>u!=null),z(y(a.value?e[1]:e[0])),z(oe(a.value?e[2]:e[1])),y(a.value?e[3]:e[2])]},([s,c,u,i])=>{if(o(),!(s!=null&&s.length)||!(c!=null&&c.length)||!(u!=null&&u.length))return;const f=Ae(i)?{...i}:i;t.push(...s.flatMap(d=>c.flatMap(p=>u.map(w=>n(d,p,w,f)))))},{flush:"post"}),l=()=>{r(),o()};return W(o),l}function ze(){const e=g(!1),t=J();return t&&ee(()=>{e.value=!0},t),e}function R(e){const t=ze();return _(()=>(t.value,!!e()))}function le(e,t,o={}){const{window:n=A,...a}=o;let r;const l=R(()=>n&&"MutationObserver"in n),s=()=>{r&&(r.disconnect(),r=void 0)},c=_(()=>{const d=y(e),p=z(d).map(M).filter(ie);return new Set(p)}),u=C(()=>c.value,d=>{s(),l.value&&d.size&&(r=new MutationObserver(t),d.forEach(p=>r.observe(p,a)))},{immediate:!0,flush:"post"}),i=()=>r==null?void 0:r.takeRecords(),f=()=>{u(),s()};return W(f),{isSupported:l,stop:f,takeRecords:i}}function ke(e,t,o={}){const{window:n=A,document:a=n==null?void 0:n.document,flush:r="sync"}=o;if(!n||!a)return I;let l;const s=i=>{l==null||l(),l=i},c=K(()=>{const i=M(e);if(i){const{stop:f}=le(a,d=>{d.map(w=>[...w.removedNodes]).flat().some(w=>w===i||w.contains(i))&&t(d)},{window:n,childList:!0,subtree:!0});s(f)}},{flush:r}),u=()=>{c(),s()};return W(u),u}function De(e){return typeof e=="function"?e:typeof e=="string"?t=>t.key===e:Array.isArray(e)?t=>e.includes(t.key):()=>!0}function lt(...e){let t,o,n={};e.length===3?(t=e[0],o=e[1],n=e[2]):e.length===2?typeof e[1]=="object"?(t=!0,o=e[0],n=e[1]):(t=e[0],o=e[1]):(t=!0,o=e[0]);const{target:a=A,eventName:r="keydown",passive:l=!1,dedupe:s=!1}=n,c=De(t);return N(a,r,i=>{i.repeat&&y(s)||c(i)&&o(i)},l)}function Le(e={}){var t;const{window:o=A,deep:n=!0,triggerOnRemoval:a=!1}=e,r=(t=e.document)!=null?t:o==null?void 0:o.document,l=()=>{var u;let i=r==null?void 0:r.activeElement;if(n)for(;i!=null&&i.shadowRoot;)i=(u=i==null?void 0:i.shadowRoot)==null?void 0:u.activeElement;return i},s=g(),c=()=>{s.value=l()};if(o){const u={capture:!0,passive:!0};N(o,"blur",i=>{i.relatedTarget===null&&c()},u),N(o,"focus",c,u)}return a&&ke(s,c,{document:r}),c(),s}const je=Symbol("vueuse-ssr-width");function Je(){const e=re()?be(je,null):null;return typeof e=="number"?e:void 0}function Y(e,t={}){const{window:o=A,ssrWidth:n=Je()}=t,a=R(()=>o&&"matchMedia"in o&&typeof o.matchMedia=="function"),r=g(typeof n=="number"),l=g(),s=g(!1),c=u=>{s.value=u.matches};return K(()=>{if(r.value){r.value=!a.value;const u=y(e).split(",");s.value=u.some(i=>{const f=i.includes("not all"),d=i.match(/\(\s*min-width:\s*(-?\d+(?:\.\d*)?[a-z]+\s*)\)/),p=i.match(/\(\s*max-width:\s*(-?\d+(?:\.\d*)?[a-z]+\s*)\)/);let w=!!(d||p);return d&&w&&(w=n>=Q(d[1])),p&&w&&(w=n<=Q(p[1])),f?!w:w});return}a.value&&(l.value=o.matchMedia(y(e)),s.value=l.value.matches)}),N(l,"change",c,{passive:!0}),_(()=>s.value)}function X(e,t={}){const{controls:o=!1,navigator:n=ue}=t,a=R(()=>n&&"permissions"in n),r=g(),l=typeof e=="string"?{name:e}:e,s=g(),c=()=>{var i,f;s.value=(f=(i=r.value)==null?void 0:i.state)!=null?f:"prompt"};N(r,"change",c,{passive:!0});const u=Me(async()=>{if(a.value){if(!r.value)try{r.value=await n.permissions.query(l)}catch{r.value=void 0}finally{c()}if(o)return ge(r.value)}});return u(),o?{state:s,isSupported:a,query:u}:s}function ct(e={}){const{navigator:t=ue,read:o=!1,source:n,copiedDuring:a=1500,legacy:r=!1}=e,l=R(()=>t&&"clipboard"in t),s=X("clipboard-read"),c=X("clipboard-write"),u=_(()=>l.value||r),i=g(""),f=g(!1),d=Re(()=>f.value=!1,a,{immediate:!1});async function p(){let h=!(l.value&&O(s.value));if(!h)try{i.value=await t.clipboard.readText()}catch{h=!0}h&&(i.value=S())}u.value&&o&&N(["copy","cut"],p,{passive:!0});async function w(h=y(n)){if(u.value&&h!=null){let v=!(l.value&&O(c.value));if(!v)try{await t.clipboard.writeText(h)}catch{v=!0}v&&b(h),i.value=h,f.value=!0,d.start()}}function b(h){const v=document.createElement("textarea");v.value=h??"",v.style.position="absolute",v.style.opacity="0",document.body.appendChild(v),v.select(),document.execCommand("copy"),v.remove()}function S(){var h,v,P;return(P=(v=(h=document==null?void 0:document.getSelection)==null?void 0:h.call(document))==null?void 0:v.toString())!=null?P:""}function O(h){return h==="granted"||h==="prompt"}return{isSupported:u,text:i,copied:f,copy:w}}function Ue(e){return JSON.parse(JSON.stringify(e))}const k=typeof globalThis<"u"?globalThis:typeof window<"u"?window:typeof global<"u"?global:typeof self<"u"?self:{},D="__vueuse_ssr_handlers__",$e=Be();function Be(){return D in k||(k[D]=k[D]||{}),k[D]}function He(e,t){return $e[e]||t}function Ke(e){return e==null?"any":e instanceof Set?"set":e instanceof Map?"map":e instanceof Date?"date":typeof e=="boolean"?"boolean":typeof e=="string"?"string":typeof e=="object"?"object":Number.isNaN(e)?"any":"number"}const Ge={boolean:{read:e=>e==="true",write:e=>String(e)},object:{read:e=>JSON.parse(e),write:e=>JSON.stringify(e)},number:{read:e=>Number.parseFloat(e),write:e=>String(e)},any:{read:e=>e,write:e=>String(e)},string:{read:e=>e,write:e=>String(e)},map:{read:e=>new Map(JSON.parse(e)),write:e=>JSON.stringify(Array.from(e.entries()))},set:{read:e=>new Set(JSON.parse(e)),write:e=>JSON.stringify(Array.from(e))},date:{read:e=>new Date(e),write:e=>e.toISOString()}},Z="vueuse-storage";function ce(e,t,o,n={}){var a;const{flush:r="pre",deep:l=!0,listenToStorageChanges:s=!0,writeDefaults:c=!0,mergeDefaults:u=!1,shallow:i,window:f=A,eventFilter:d,onError:p=m=>{console.error(m)},initOnMounted:w}=n,b=(i?g:H)(typeof t=="function"?t():t),S=_(()=>y(e));if(!o)try{o=He("getDefaultStorage",()=>{var m;return(m=A)==null?void 0:m.localStorage})()}catch(m){p(m)}if(!o)return b;const O=y(t),h=Ke(O),v=(a=n.serializer)!=null?a:Ge[h],{pause:P,resume:V}=Ve(b,()=>fe(b.value),{flush:r,deep:l,eventFilter:d});C(S,()=>x(),{flush:r}),f&&s&&Fe(()=>{o instanceof Storage?N(f,"storage",x,{passive:!0}):N(f,Z,ve),w&&x()}),w||x();function F(m,E){if(f){const T={key:S.value,oldValue:m,newValue:E,storageArea:o};f.dispatchEvent(o instanceof Storage?new StorageEvent("storage",T):new CustomEvent(Z,{detail:T}))}}function fe(m){try{const E=o.getItem(S.value);if(m==null)F(E,null),o.removeItem(S.value);else{const T=v.write(m);E!==T&&(o.setItem(S.value,T),F(E,T))}}catch(E){p(E)}}function de(m){const E=m?m.newValue:o.getItem(S.value);if(E==null)return c&&O!=null&&o.setItem(S.value,v.write(O)),O;if(!m&&u){const T=v.read(E);return typeof u=="function"?u(T,O):h==="object"&&!Array.isArray(T)?{...O,...T}:T}else return typeof E!="string"?E:v.read(E)}function x(m){if(!(m&&m.storageArea!==o)){if(m&&m.key==null){b.value=O;return}if(!(m&&m.key!==S.value)){P();try{(m==null?void 0:m.newValue)!==v.write(b.value)&&(b.value=de(m))}catch(E){p(E)}finally{m?L(V):V()}}}}function ve(m){x(m.detail)}return b}function ft(e,t,o={}){const{window:n=A,initialValue:a,observe:r=!1}=o,l=g(a),s=_(()=>{var u;return M(t)||((u=n==null?void 0:n.document)==null?void 0:u.documentElement)});function c(){var u;const i=y(e),f=y(s);if(f&&n&&i){const d=(u=n.getComputedStyle(f).getPropertyValue(i))==null?void 0:u.trim();l.value=d||l.value||a}}return r&&le(s,c,{attributeFilter:["style","class"],window:n}),C([s,()=>y(e)],(u,i)=>{i[0]&&i[1]&&i[0].style.removeProperty(i[1]),c()},{immediate:!0}),C([l,s],([u,i])=>{const f=y(e);i!=null&&i.style&&f&&(u==null?i.style.removeProperty(f):i.style.setProperty(f,u))},{immediate:!0}),l}function dt(e,t,o={}){const{window:n=A,...a}=o;let r;const l=R(()=>n&&"ResizeObserver"in n),s=()=>{r&&(r.disconnect(),r=void 0)},c=_(()=>{const f=y(e);return Array.isArray(f)?f.map(d=>M(d)):[M(f)]}),u=C(c,f=>{if(s(),l.value&&n){r=new ResizeObserver(t);for(const d of f)d&&r.observe(d,a)}},{immediate:!0,flush:"post"}),i=()=>{s(),u()};return W(i),{isSupported:l,stop:i}}function qe(e,t,o={}){const{root:n,rootMargin:a="0px",threshold:r=0,window:l=A,immediate:s=!0}=o,c=R(()=>l&&"IntersectionObserver"in l),u=_(()=>{const w=y(e);return z(w).map(M).filter(ie)});let i=I;const f=g(s),d=c.value?C(()=>[u.value,M(n),f.value],([w,b])=>{if(i(),!f.value||!w.length)return;const S=new IntersectionObserver(t,{root:M(b),rootMargin:a,threshold:r});w.forEach(O=>O&&S.observe(O)),i=()=>{S.disconnect(),i=I}},{immediate:s,flush:"post"}):I,p=()=>{i(),d(),f.value=!1};return W(p),{isSupported:c,isActive:f,pause(){i(),f.value=!1},resume(){f.value=!0},stop:p}}function vt(e,t={}){const{window:o=A,scrollTarget:n,threshold:a=0,rootMargin:r,once:l=!1}=t,s=g(!1),{stop:c}=qe(e,u=>{let i=s.value,f=0;for(const d of u)d.time>=f&&(f=d.time,i=d.isIntersecting);s.value=i,l&&Ie(s,()=>{c()})},{root:n,window:o,threshold:a,rootMargin:y(r)});return s}function pt(e,t={}){const{initialValue:o=!1,focusVisible:n=!1,preventScroll:a=!1}=t,r=g(!1),l=_(()=>M(e)),s={passive:!0};N(l,"focus",u=>{var i,f;(!n||(f=(i=u.target).matches)!=null&&f.call(i,":focus-visible"))&&(r.value=!0)},s),N(l,"blur",()=>r.value=!1,s);const c=_({get:()=>r.value,set(u){var i,f;!u&&r.value?(i=l.value)==null||i.blur():u&&!r.value&&((f=l.value)==null||f.focus({preventScroll:a}))}});return C(l,()=>{c.value=o},{immediate:!0,flush:"post"}),{focused:c}}const Qe="focusin",Ye="focusout",Xe=":focus-within";function mt(e,t={}){const{window:o=A}=t,n=_(()=>M(e)),a=g(!1),r=_(()=>a.value),l=Le(t);if(!o||!l.value)return{focused:r};const s={passive:!0};return N(n,Qe,()=>a.value=!0,s),N(n,Ye,()=>{var c,u,i;return a.value=(i=(u=(c=n.value)==null?void 0:c.matches)==null?void 0:u.call(c,Xe))!=null?i:!1},s),{focused:r}}function wt(e,t,o={}){const{window:n=A}=o;return ce(e,t,n==null?void 0:n.localStorage,o)}function yt(e){const t=Y("(prefers-color-scheme: light)",e),o=Y("(prefers-color-scheme: dark)",e);return _(()=>o.value?"dark":t.value?"light":"no-preference")}function ht(e,t,o={}){const{window:n=A}=o;return ce(e,t,n==null?void 0:n.sessionStorage,o)}function gt(e,t,o,n={}){var a,r,l;const{clone:s=!1,passive:c=!1,eventName:u,deep:i=!1,defaultValue:f,shouldEmit:d}=n,p=J(),w=o||(p==null?void 0:p.emit)||((a=p==null?void 0:p.$emit)==null?void 0:a.bind(p))||((l=(r=p==null?void 0:p.proxy)==null?void 0:r.$emit)==null?void 0:l.bind(p==null?void 0:p.proxy));let b=u;t||(t="modelValue"),b=b||`update:${t.toString()}`;const S=v=>s?typeof s=="function"?s(v):Ue(v):v,O=()=>_e(e[t])?S(e[t]):f,h=v=>{d?d(v)&&w(b,v):w(b,v)};if(c){const v=O(),P=H(v);let V=!1;return C(()=>e[t],F=>{V||(V=!0,P.value=S(F),L(()=>V=!1))}),C(P,F=>{!V&&(F!==e[t]||i)&&h(F)},{deep:i}),P}else return _({get(){return O()},set(v){h(v)}})}export{vt as A,ft as a,ct as b,yt as c,ht as d,ce as e,gt as f,N as g,wt as h,M as i,A as j,U as k,nt as l,ot as m,it as n,lt as o,ze as p,et as q,rt as r,mt as s,ut as t,pt as u,Oe as v,tt as w,at as x,st as y,dt as z};
