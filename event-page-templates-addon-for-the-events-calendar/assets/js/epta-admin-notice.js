jQuery(document).ready(function ($) {
  $(document).on("click", "#epta-review-box .epta_dismiss_notice", function (event) {
      var $this = $(this);
      var wrapper=$this.parents(".epta-feedback-notice-wrapper");
      var ajaxURL=wrapper.data("ajax-url");
      var ajaxCallback=wrapper.data("ajax-callback");
      var slug = wrapper.data("plugin-slug");
      var id = wrapper.attr("id");
      var wp_nonce = wrapper.data("wp-nonce");
      $.post(ajaxURL, { "action":ajaxCallback,"slug":slug,"id":id,"_nonce":wp_nonce }, function( data ) {
          wrapper.slideUp("fast");
        })
  });

  jQuery(document).ready(function ($) {
    $(".epta-new-plugin_admin_notice").css("border","2px solid red");
    
    $(document).on("click",".epta-new-plugin_admin_notice.notice-success", function (event) {
        var $this = $(this);
        var wrapper=$this;
        var ajaxURL=wrapper.data("ajax-url");
        var id = wrapper.data("plugin-slug");
        var wp_nonce = wrapper.data("wp-nonce");
        $.post(ajaxURL, { "action":"epta_admin_notice","id":id,"_nonce":wp_nonce }, function( data ) {
            wrapper.slideUp("fast");
          }, "json");
    });
});
});