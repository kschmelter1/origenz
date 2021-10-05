<?php
$content = get_field('sections');
  if ($content) {
    $order = 0;
    foreach ($content as $block) :
      include( locate_template( 'templates/theme/block-'.$block['acf_fc_layout'].'.php', false, false ) );
      $order++;
    endforeach;
  }
?>
