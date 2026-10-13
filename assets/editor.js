/* Apply initial preferences once; users can subsequently change fullscreen. */
wp.domReady(function () {
 const options = window.siteEssentialsEditor || {};
 const preferences = wp.data.dispatch('core/preferences');
 if (options.fullscreen) preferences.set('core/edit-post', 'fullscreenMode', false);
 if (options.welcome) preferences.set('core/edit-post', 'welcomeGuide', false);
});
