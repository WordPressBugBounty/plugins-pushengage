jQuery(function ($) {
    'use strict';
  
    var pushengageDeactivationFeedback = {
      deactivationLink: '',
      
      init: function () {
        pushengageDeactivationFeedback.bindEvents();
      },

      showPopup: function (data) {
        $('body').find('.pushengage-deactivation-feedback-modal').fadeIn();
        
        // Store the deactivation link
        pushengageDeactivationFeedback.deactivationLink = data.deactivation_link;
      },
      
      hidePopup: function () {
        $('body').find('.pushengage-deactivation-feedback-modal').fadeOut();
      },

      submitFeedback: function () {
        var cause = $('input[name="deactivation_reason"]:checked').val();
        var comment = $('textarea[name="feedback_details"]').val();

        // add loading state
        $('.pushengage-submit-skip-deactivate').text('Processing...');
        $('.pushengage-submit-skip-deactivate').prop('disabled', true);

        // Send feedback to server
        $.ajax({
          url: pushengageDeactivationFeedbackVars.ajax_url,
          type: 'POST',
          data: {
            action: 'pe_deactivation_feedback',
            cause: cause,
            comment: comment,
            _wpnonce: pushengageDeactivationFeedbackVars.nonce
          },
          success: function(response) {
            // Proceed with deactivation
            window.location.href = pushengageDeactivationFeedback.deactivationLink;
          },
          error: function() {
            // Proceed with deactivation even if feedback fails
            window.location.href = pushengageDeactivationFeedback.deactivationLink;
          }
        });
      },

      getFeedbackPlaceholder: function (type) {
        var placeholders = {
            'did_not_work': "Could you tell us a bit more what's not working?",
            'better_plugin': 'Tell us which plugin you switched to and what made you choose it.',
            'missing_feature': 'Could you tell us more about that feature?',
            'did_not_work_as_expected': "Tell us what feature didn't work the way you expected.",
            'temporary_deactivation': "Describe the problem you're investigating and why you think our plugin may be the cause?",
            'other': 'Could you tell us a bit more?'
        }
        return placeholders[type];
      },
  
      bindEvents: function () {
        // Bind deactivation link click
        $('#the-list').on(
          'click',
          'a#deactivate-pushengage',
          function (e) {
            e.preventDefault();
            pushengageDeactivationFeedback.showPopup({deactivation_link: $(this).attr('href')});
          }
        );
        
        // Bind radio button change
        $(document).on('change', 'input[name="deactivation_reason"]', function() {
          var value = $(this).val();

          // Update feedback details placeholder.
          var feedbackDetails = $('.pushengage-deactivation-feedback-modal textarea[name=feedback_details]');
          feedbackDetails.attr('placeholder', pushengageDeactivationFeedback.getFeedbackPlaceholder(value));

          // Show feedback details field.
          $('.pushengage-feedback-details').show();

          // set btn text to submit and deactivate.
          $('.pushengage-submit-skip-deactivate').text('Submit & Deactivate');
        });
        
        // Bind cancel button
        $(document).on('click', '.pushengage-cancel', function () {
          pushengageDeactivationFeedback.hidePopup();
        });
        
        // Bind submit and deactivate
        $(document).on('click', '.pushengage-submit-skip-deactivate', function () {
          var selectedReason = $('input[name="deactivation_reason"]:checked').val();
          
          if (selectedReason) {
            pushengageDeactivationFeedback.submitFeedback();
          } else {
            window.location.href = pushengageDeactivationFeedback.deactivationLink;
          } 
        });
        
        // Close modal when clicking outside
        $(document).on('click', '.pushengage-deactivation-feedback-modal', function(e) {
          if (e.target === this) {
            pushengageDeactivationFeedback.hidePopup();
          }
        });

        // Close on esc key
        $(document).on('keydown', function(e) {
          if (e.key === 'Escape') {
            pushengageDeactivationFeedback.hidePopup();
          }
        });
      },
    };
  
    pushengageDeactivationFeedback.init();
  });
  