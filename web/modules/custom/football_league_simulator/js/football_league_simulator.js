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

      if (!$('#play-all-matches').data('clicked')) {
        $('#play-all-matches', context).on('click', function (e) {
          e.preventDefault();
          $.ajax({
            url: Drupal.url('football-league/play-all-matches'),
            type: 'POST',
            dataType: 'json',
            success: function (response) {
              if (response.success) {
                location.reload();
              } else {
                alert('Error when generating all matches.');
              }
            }
          });

          // Mark that the handler has been bound.
          $('#play-all-matches').data('clicked', true);
        });
      }

      if (!$('#play-new-tournament').data('clicked')) {
        $('#play-new-tournament', context).on('click', function (e) {
          e.preventDefault();
          $.ajax({
            url: Drupal.url('football-league/play-new-tournament'),
            type: 'POST',
            dataType: 'json',
            success: function (response) {
              if (response.success) {
                location.reload();
              } else {
                alert('Error when generating all matches.');
              }
            }
          });

          // Mark that the handler has been bound.
          $('#play-new-tournament').data('clicked', true);
        });
      }

      if (!$('#save-results').data('clicked')) {
        $('#save-results', context).on('click', function (e) {
          e.preventDefault();

          const formData = $('#edit-results-form').serialize();
          $.ajax({
            url: Drupal.url('football-league/save-result'),
            type: 'POST',
            data: formData,
            success: function (response) {
              if (response.success) {
                // alert(formData);
                location.reload();
              } else {
                alert('Error when saving result.');
              }
            }
          });

          // Mark that the handler has been bound.
          $('#save-results').data('clicked', true);
        });
      }
    }
  };
})(jQuery, Drupal);
