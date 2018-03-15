jQuery(document).ready(function() {
  // Show the single suburb upload form
  jQuery("#add-new-suburb").click(function() {
    jQuery("#add-new-suburb-form").show();
    jQuery("#add-buttons-wrap").hide();
  });

  // Show the suburb bulk upload form
  jQuery("#bulk-upload-suburb").click(function() {
    jQuery("#bulk-upload-suburb-form").show();
    jQuery("#add-buttons-wrap").hide();
  });

  // Hide either form
  jQuery(".suburb-submit-cancel").click(function() {
    jQuery("#bulk-upload-suburb-form").hide();
    jQuery("#add-new-suburb-form").hide();
    jQuery("#add-buttons-wrap").show();
  });
});
