jQuery(function($) {
  var i = 3;
  $('#add_button').click(addAnotherTextBox);
  function addAnotherTextBox() {
    $("#ratingnames").append("<br><input placeholder='RATING TYPE. Enter a description for the type of rating. Example: Excellent performer or Poor Communications Skills etc.' type='text' name='desc_" + i + "' >");
    i++;
  }
  $('.success').delay(5000).fadeOut(1000);
});