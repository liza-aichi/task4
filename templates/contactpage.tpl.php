<br>
<?php  
 if(isset($items['Photo'])) print theme('image_style',array('style_name' => 'contact_img', 'path' => $items['Photo']));
?><br>
<p><b>Category:  </b><?php print $items['Category'] ?></p>
<?php  if(isset($items['Email'])): ?>
<p><b>Email:  </b><a href="mailto:<?php print $items['Email']; ?>"><?php print $items['Email']; ?></a></p><?php endif; ?>
<?php if(strlen($items['Phone'])>0): ?>
<p><b>Phone:  </b><?php print $items['Phone'] ?></p><?php endif; ?>
<p><b>Birthday:  </b><?php print $items['Birthday'] ?></p>
<div style="width:650px;  "><?php print $items['Note']; ?></div>


