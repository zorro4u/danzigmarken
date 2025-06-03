<?php /* 
Matomo Code
------------
(matomo.* -> mato.*) 
uBlock blockiert den RückPing zu matomo.php
*/ ?>

<?php /* */ ?>
<script> 
  var _paq = window._paq = window._paq || [];
  _paq.push(["setDocumentTitle", document.domain + "/" + document.title]);
  _paq.push(["setCookieDomain", "*.danzigmarken.de"]);
  _paq.push(["setDomains", ["*.danzigmarken.de","*.danzigmarken.de/*","*.danzigmarken.de/index.html","*.danzigmarken.de/index.php","*.danzigmarken.de/index2.php","*.danzigmarken.de/details.php","*.danzigmarken.de/change.php","*.danzigmarken.de/settings.php","*.danzigmarken.de/admin.php","*.danzigmarken.de/register.php","*.danzigmarken.de/login.php","*.danzigmarken.de/logout.php","*.danzigmarken.de/pwforget.php","*.danzigmarken.de/email.php"]]);
  _paq.push(["disableCampaignParameters"]);
  _paq.push(["disableCookies"]);
  _paq.push(['trackPageView']);
  _paq.push(['enableLinkTracking']);
  (function() {
    var u="//matomo.danzigmarken.de/";
    _paq.push(['setTrackerUrl', u+'mato.php']);
    _paq.push(['setSiteId', '1']);
    var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
    g.async=true; g.src=u+'mato.js'; s.parentNode.insertBefore(g,s);
  })(); 
</script>


<?php /* 
<noscript>
<p><img referrerpolicy="no-referrer-when-downgrade" src="//matomo.danzigmarken.de/mato.php?idsite=1&amp;rec=1" style="border:0;" alt="" ></p>
</noscript>
*/ ?>

<?php // Matomo Tag Manager ?>


