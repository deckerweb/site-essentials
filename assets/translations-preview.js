/** Keep the preview alongside the native translation update form. */
document.addEventListener('DOMContentLoaded', function () {
 const preview=document.querySelector('.se-translation-preview');
 const form=document.querySelector('form[name="upgrade-translations"]');
 if(preview && form) form.before(preview);
});
