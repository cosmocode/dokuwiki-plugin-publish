//Autocomment for Approve Button
function approval_checkbox(text) {
  if(text == "") { return true; }
  var cb=document.getElementById('approved');
  if(cb == null) { return true; } //huh?
  if(!cb.checked) { return true; } //this only fires on set
  var sum=document.getElementById('edit__summary');
  if(sum == null) { return true; } //huh?
  if(sum.value != '') { return true; } // already set
  sum.value = text;

  // in case enforced Comments are installed
  var btn = document.getElementById('edbtn__save');
  if(btn == null) { return true; } //huh?
  btn.className = 'button';
  btn.disabled = false;

  return true;
}
