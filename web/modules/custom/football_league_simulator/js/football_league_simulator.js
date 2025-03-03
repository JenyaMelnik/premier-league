(function ($, Drupal) {
  Drupal.behaviors.footballLeagueSimulator = {
    attach: function (context, settings) {
      // Check if the handler is bound.
      if (!$('#simulate-week').data('clicked')) {
        $('#simulate-week', context).on('click', function (e) {
          e.preventDefault();
          $.ajax({
            url: Drupal.url('football-league/simulate-week'),
            type: 'POST',
            dataType: 'json',
            success: function (response) {
              if (response.success) {
                location.reload();
              } else {
                alert('Error when generating the week.');
              }
            }
          });

          // Mark that the handler has been bound.
          $('#simulate-week').data('clicked', true);
        });
      }
    }
  };
})(jQuery, Drupal);
