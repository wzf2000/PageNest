/* Mark only this theme's native controls; WordPress keeps navigation and persistence. */
(function () {
  if (!window.wp || !wp.customize) return;
  wp.customize.bind('ready', function () {
    wp.customize.section.each(function (section) {
      if (section.id.indexOf('pagenest_') !== 0) return;
      section.container.addClass('pagenest-customizer-section');
      section.contentContainer.addClass('pagenest-customizer-content');
    });
    wp.customize.control.each(function (control) {
      if (control.id.indexOf('pagenest_') !== 0) return;
      control.container.addClass('pagenest-customizer-control');
      if (control.id.indexOf('pagenest_home_') === 0 && /_(enabled|order)$/.test(control.id)) {
        control.container.attr(
          'data-pagenest-control-group',
          control.id.replace(/_(enabled|order)$/, ''),
        );
      }
    });
  });
})();
