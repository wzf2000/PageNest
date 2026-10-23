(() => {
  const placeAccountOptions = () => {
    const form = document.querySelector('body.pagenest-login #loginform');
    const options = form?.querySelector(':scope > .pagenest-social-login-options');
    const submit = form?.querySelector(':scope > .submit');
    // Keep the existing form, provider links and validation controls in their native flow.
    if (options && submit) submit.after(options);
  };
  if (document.readyState === 'loading')
    document.addEventListener('DOMContentLoaded', placeAccountOptions, { once: true });
  else placeAccountOptions();
})();
