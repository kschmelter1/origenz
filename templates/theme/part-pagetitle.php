<?php $bg = get_field('header_background','options'); ?>
<div class="page-title" <?php echo ($bg ? 'style="background-image:url('.$bg['url'].')"' : ''); ?>>
  <div class="container-fluid">
    <h1><?php echo get_the_title(); ?></h1>
  </div>
</div>
