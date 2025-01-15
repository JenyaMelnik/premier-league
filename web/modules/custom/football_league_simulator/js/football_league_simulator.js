(function ($, Drupal) {
  Drupal.behaviors.footballLeagueEditResults = {
    attach: function (context, settings) {
      $('#save-results', context).once('saveResultsAjax').on('click', function () {
        const formData = $('#edit-results-form').serialize();

        $.ajax({
          url: '/football-league/save-results',
          type: 'POST',
          data: formData,
          dataType: 'json',
          success: function (response) {
            if (response.status === 'success') {
              let tableHtml = '<thead><tr><th>Team</th><th>Points</th><th>GF</th><th>GA</th><th>GD</th><th>Win Chance (%)</th></tr></thead><tbody>';
              response.table.forEach(team => {
                tableHtml += `<tr>
                  <td>${team.name}</td>
                  <td>${team.points}</td>
                  <td>${team.goals_for}</td>
                  <td>${team.goals_against}</td>
                  <td>${team.goal_difference}</td>
                  <td>${team.win_chance}%</td>
                </tr>`;
              });
              tableHtml += '</tbody>';
              $('#league-table table').html(tableHtml);

              alert('Results updated successfully!');
            } else {
              alert('Failed to update results.');
            }
          },
          error: function () {
            alert('An error occurred while saving the results.');
          },
        });
      });
    },
  };
})(jQuery, Drupal);
