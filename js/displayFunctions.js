//
// Function to toggle the display of a block element in a page.
//
// There is a CSS-only mechanism to do this, but it does not work properly
// on Opera and apparently Safari.
//
function showBlock(el) {
  var objEl = document.getElementById(el);

  // Find out the value of the CSS variable "display".
  // Need to use this round-about method since global styles are not
  // recognised when looking at an object's style class
  // (i.e. objEl.style).
  if (objEl.currentStyle) // IE 5
    var displayProp = objEl.currentStyle["display"];
  else if (window.getComputedStyle) { // MOZILLA
    var tstyle = window.getComputedStyle(objEl, "");
    var displayProp = tstyle.getPropertyValue("display");
  }

  // Toggle the display property.
  if(displayProp=="block") {
    objEl.style.display="none";
  }
  else {
    objEl.style.display="block";
  }
}


function hideBlock(el) {
  var objEl = document.getElementById(el);

	objEl.style.display="none";
}
