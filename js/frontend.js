jQuery(document).ready(function() {
  // Timeout to only run the function once the user stops typing and prevent AJAX requests from overlapping
  var typingTimer;
  var doneTypingInterval = 1000; // 1 second
  var $input = jQuery(".gf-postcode");

  // on change keyup paste, start the countdown
  $input.on("change keyup paste", function() {
    clearTimeout(typingTimer);
    typingTimer = setTimeout(doneTyping, doneTypingInterval);
  });

  // on keydown, clear the countdown
  $input.on("keydown", function() {
    clearTimeout(typingTimer);
  });

  // User is done typing, now we do the AJAX request
  function doneTyping() {
    var $this = jQuery(this);

    // Hide Submit / Next button until we're done checking if the pc is valid
    gfsuburbs_hide_elements($this.parent(".gf-postcode-wrap"));

    var formid = $this.attr("id").replace("gf-postcode_", "");
    var inputid = $this.attr("data-input_id");
    var postcode = $this.val();

    var data = {
      action: "gf_check_postcode",
      postcode: postcode
    };

    jQuery.post(ajaxurl, data, function(response) {
      if (!response || response == false) {
        // If the postcode was invalid, do a 2nd AJAX request to get the invlaid pc link
        var data = {
          action: "gf_get_invalid_postcode_link"
        };
        jQuery.post(
          ajaxurl,
          data,
          function(response) {
            var $link =
              "We don't offer our services at your location. Please <a href='" +
              response +
              "'>contact us for a custom quote</a>";
            $this.next(".gf-suburbs-wrap").html("<p>" + $link + "</p>");
          },
          "text"
        );
      } else {
        // Display a list of available suburbs for the postcode
        var suburbs = JSON.parse(response);
        var select = jQuery("<select />", {
          class: "gf-suburbs-select",
          id: inputid,
          name: inputid
        });

        // Default option
        jQuery("<option />", {
          value: "",
          text: "Select a suburb..."
        }).appendTo(select);

        jQuery.each(suburbs, function(k, v) {
          jQuery("<option />", {
            value: v.suburb + " (postcode: " + v.postcode + ")",
            text: v.suburb
          }).appendTo(select);
        });

        $this.next(".gf-suburbs-wrap").html(select);
      }
    });
  }

  jQuery(".gf-postcode-wrap").each(function(k, v) {
    // Hide Submit / Next button until the user enters his postcode
    gfsuburbs_hide_elements(jQuery(this));
  });

  jQuery(".gf-suburbs-wrap").on("change", ".gf-suburbs-select", function() {
    // Once they enter a pc and select a suburb, show the Submit / Next button
    var formid = jQuery(this)
      .parent()
      .attr("data-formid");
    var postcode_wrap = jQuery("#gf-postcode-wrap_" + formid);
    if (postcode_wrap.hasClass("multipage_form")) {
      var form = jQuery("#gform_" + formid);
      form.find(".gform_next_button").slideDown();
      jQuery("#gform_submit_button_" + formid).show();
    } else {
      postcode_wrap
        .parent("li")
        .nextAll("li")
        .slideDown();
      jQuery("#gform_submit_button_" + formid).slideDown();
    }
  });
});

function gfsuburbs_hide_elements($this) {
  var formid = $this.attr("id").replace("gf-postcode-wrap_", "");

  if ($this.hasClass("multipage_form")) {
    if ($this.is(":visible")) {
      var form = jQuery("#gform_" + formid);
      form.find(".gform_next_button").hide();
      jQuery("#gform_submit_button_" + formid).hide();
    }
  } else {
    var li = $this.parent("li");
    li.nextAll("li").hide();
    jQuery("#gform_submit_button_" + formid).hide();
  }
}
