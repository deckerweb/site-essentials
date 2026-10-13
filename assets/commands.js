(function () {
 'use strict';
 if (!window.siteEssentialsCommands || !window.wp?.commands?.store || !window.wp?.data || !window.wp?.domReady) return;
 wp.domReady(function () {
  wp.data.dispatch(wp.commands.store).registerCommand({
   name:'site-essentials/reports', label:siteEssentialsCommands.label, category:'view',
   callback:function ({close}) { close(); window.location.assign(siteEssentialsCommands.url); }
  });
 });
})();
