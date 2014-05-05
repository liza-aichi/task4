<br>
<?php  
 if(isset($items['Photo'])) {print theme('image_style',array('style_name' => 'contact_img', 'path' => $items['Photo']));
 } ?><br>
<p><b><?php print t("Category: "); ?></b><?php print $items['Category'] ?></p>
<?php  if(isset($items['Email'])): ?>
<p><b><?php print t("Email: "); ?>  </b> <?php print l($items['Email'],  "mailto:".$items['Email'],array('absolute'=>TRUE));
    ?></p>

<?php endif; ?>
<?php if(strlen($items['Phone'])>0): ?>
<p><b><?php print t("Phone: "); ?>   </b><?php print $items['Phone'] ?></p><?php endif; ?>
<p><b><?php print t("Birthday: "); ?>   </b><?php print $items['Birthday'] ?></p>
<div style="width:650px;  "><?php print $items['Note']; ?></div>


