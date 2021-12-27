/**
  * Get the AJAX object
  */
function getAJAX() {
  var ajaxRequest;

  try {
    ajaxRequest = new XMLHttpRequest();
  } catch (e) {
    try {
      ajaxRequest = new ActiveXObject("Msxml2.XMLHTTP");
    } catch (e) {
      try {
        ajaxRequest = new ActiveXObject("Microsoft.XMLHTTP");
      } catch (e) {
        alert("An error occurred. Your browser may not be supported by this website");
        return null;
      }
    }
  }

  return ajaxRequest;
}
