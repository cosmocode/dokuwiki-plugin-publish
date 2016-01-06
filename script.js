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


jQuery( document ).ready(function () {
    jQuery('button.publish__approveNS').click(function(evt){
        var $_this = jQuery(this);
        var namespace = $_this.attr('ns');
        jQuery.post(
            DOKU_BASE + 'lib/exe/ajax.php',
            {
                call: 'plugin_publish_approveNS',
                namespace: namespace
            },
            function(data) {
                $_this.parent().parent().siblings('tr.apr_table').each(function(index) {
                        var id = jQuery(this).find('a').first().text();
                        var pageNamespace = id.substr(0,id.lastIndexOf(':'));
                        if (pageNamespace === namespace) {
                            jQuery(this).hide('slow');
                        }
                    }
                );
                $_this.parent().parent().hide('slow');
            },
            'json'
        );
    });
});
