'use strict';
const nav=document.querySelector('.navigation'),menu=document.querySelector('.menu-toggle'),links=document.querySelector('#nav-links');
function updateNav(){nav.classList.toggle('scrolled',window.scrollY>24)}
updateNav();window.addEventListener('scroll',updateNav,{passive:true});
menu.addEventListener('click',()=>{const open=menu.getAttribute('aria-expanded')!=='true';menu.setAttribute('aria-expanded',String(open));menu.setAttribute('aria-label',open?'Close navigation':'Open navigation');links.classList.toggle('open',open)});
links.querySelectorAll('a').forEach(link=>link.addEventListener('click',()=>{menu.setAttribute('aria-expanded','false');menu.setAttribute('aria-label','Open navigation');links.classList.remove('open')}));
const config=window.CGM_SITE||{};
function publicUrl(value){try{const url=new URL(value);return url.protocol==='https:'?url.href:null}catch{return null}}
const download=publicUrl(config.downloadUrl);
if(download){const link=document.getElementById('download-link');link.href=download;link.hidden=false;document.getElementById('download-pending').hidden=true}
const contact=publicUrl(config.contactUrl);
if(contact){const link=document.getElementById('contact-link');link.href=contact;link.hidden=false;link.target='_blank';link.rel='noopener noreferrer';document.querySelectorAll('.plan-link').forEach(link=>{link.href=contact;link.textContent='Get a code';link.target='_blank';link.rel='noopener noreferrer'})}
document.querySelectorAll('[data-days]').forEach(price=>{const value=config.prices?.[price.dataset.days];if(typeof value==='string'&&value.trim())price.textContent=value.trim()});
document.getElementById('year').textContent=String(new Date().getFullYear());
