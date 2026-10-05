/* ============================================================
   Leno — Public website JS (vanilla, không phụ thuộc jQuery)
   ============================================================ */
(function(){
  'use strict';

  /* ---------- Analytics (GA4) ----------
     Để trống GA_MEASUREMENT_ID = tắt hẳn analytics, không ảnh hưởng website. */
  var GA_MEASUREMENT_ID = '';

  window.papInitAnalytics = function(){
    if ( ! GA_MEASUREMENT_ID) return;
    var s1 = document.createElement('script');
    s1.async = true;
    s1.src = 'https://www.googletagmanager.com/gtag/js?id=' + GA_MEASUREMENT_ID;
    document.head.appendChild(s1);

    window.dataLayer = window.dataLayer || [];
    function gtag(){ window.dataLayer.push(arguments); }
    window.gtag = gtag;
    gtag('js', new Date());
    gtag('config', GA_MEASUREMENT_ID);
  };

  window.papTrack = function(eventName, params){
    if (typeof window.gtag === 'function'){
      window.gtag('event', eventName, params || {});
    }
    // console.debug('[papTrack]', eventName, params || {});
  };

  /* ---------- Mobile menu: tự đóng khi bấm 1 link ---------- */
  document.addEventListener('DOMContentLoaded', function(){
    var navCollapseEl = document.getElementById('papNav');
    if (navCollapseEl){
      navCollapseEl.querySelectorAll('a.nav-link, a.btn').forEach(function(link){
        link.addEventListener('click', function(){
          if (window.bootstrap && navCollapseEl.classList.contains('show')){
            var collapse = window.bootstrap.Collapse.getOrCreateInstance(navCollapseEl);
            collapse.hide();
          }
        });
      });
    }

    /* ---------- Gallery filter ---------- */
    var filterBtns = document.querySelectorAll('.pap-filter-btn');
    var galleryItems = document.querySelectorAll('.pap-gallery-item');
    filterBtns.forEach(function(btn){
      btn.addEventListener('click', function(){
        filterBtns.forEach(function(b){ b.classList.remove('active'); });
        btn.classList.add('active');
        var filter = btn.getAttribute('data-filter');
        galleryItems.forEach(function(item){
          var match = filter === 'all' || item.getAttribute('data-category') === filter;
          item.hidden = ! match;
        });
      });
    });

    /* ---------- Lightbox ---------- */
    var lightbox = document.getElementById('papLightbox');
    if (lightbox){
      var lightboxImg = document.getElementById('papLightboxImg');
      var closeBtn = document.getElementById('papLightboxClose');

      document.querySelectorAll('[data-lightbox]').forEach(function(link){
        link.addEventListener('click', function(e){
          e.preventDefault();
          lightboxImg.src = link.getAttribute('href');
          lightboxImg.alt = link.querySelector('img') ? link.querySelector('img').alt : '';
          lightbox.hidden = false;
        });
      });

      function closeLightbox(){ lightbox.hidden = true; lightboxImg.src = ''; }
      closeBtn.addEventListener('click', closeLightbox);
      lightbox.addEventListener('click', function(e){ if (e.target === lightbox) closeLightbox(); });
      document.addEventListener('keydown', function(e){ if (e.key === 'Escape' && ! lightbox.hidden) closeLightbox(); });
    }

    /* ---------- Fade-in on scroll (nhẹ, tôn trọng prefers-reduced-motion qua CSS) ---------- */
    var fadeTargets = document.querySelectorAll('.pap-card, .pap-promo-card, .pap-feature, .pap-section-title');
    fadeTargets.forEach(function(el){ el.classList.add('pap-fade-in'); });

    if ('IntersectionObserver' in window){
      var observer = new IntersectionObserver(function(entries){
        entries.forEach(function(entry){
          if (entry.isIntersecting){
            entry.target.classList.add('pap-visible');
            observer.unobserve(entry.target);
          }
        });
      }, { threshold: 0.15 });
      fadeTargets.forEach(function(el){ observer.observe(el); });
    } else {
      fadeTargets.forEach(function(el){ el.classList.add('pap-visible'); });
    }
  });
})();
