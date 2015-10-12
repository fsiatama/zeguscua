

window.google = window.google || {};
google.maps = google.maps || {};
(function() {
  
  function getScript(src) {
    document.write('<' + 'script src="' + src + '"' +
                   ' type="text/javascript"><' + '/script>');
  }
  
  getScript("https://maps.googleapis.com/maps/api/js?key=AIzaSyDnycWatbGyK6ldFqErjFtko1yeMclNUOA&amp;sensor=true");
})();