(function () {
 'use strict';
 if (!window.siteEssentialsCommands || !window.wp?.commands?.store || !window.wp?.data || !window.wp?.domReady) return;
 wp.domReady(function () {
  const config = window.siteEssentialsCommands;
  const entries = config.commands || [{name:'site-essentials/reports',label:config.label,url:config.url}];
  entries.forEach(function (entry) {
   wp.data.dispatch(wp.commands.store).registerCommand({
    name:entry.name, label:entry.label, category:'view',
    callback:function ({close}) { close(); window.location.assign(entry.url); }
   });
  });
 });
})();
